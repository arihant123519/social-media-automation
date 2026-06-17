<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Unified content-insights backbone shared by the four Growth-Intelligence tools
 * (Content Health Scorecard, All-Platform Command Center, Best-Time calculator,
 * Viral Predictor).
 *
 * It produces ONE normalized list of "content items" per client across every
 * platform. Local published-post data (always present) is the backbone; on top
 * of that it makes best-effort Instagram Graph + YouTube Data calls to enrich
 * each item with REAL reach / saves / shares / plays. When a platform isn't
 * connected the items still render — they just carry no live metrics (`live`
 * false), and the consuming tools fall back to the AI quality score.
 *
 * Every item is a flat array:
 *   platform, format, format_label, title, caption, url, published_at (Carbon),
 *   score, reach, impressions, saves, shares, likes, comments, plays, views,
 *   watch_time, engagement, eng_rate, live
 */
class ContentInsightsService
{
    private const IG_BASE = 'https://graph.facebook.com/v18.0';
    private const YT_BASE = 'https://www.googleapis.com/youtube/v3';

    /** Human labels + a stable colour key per content format. */
    public const FORMATS = [
        'reels'       => ['label' => 'Reel',        'color' => '#e1306c'],
        'short_video' => ['label' => 'Short',       'color' => '#ff0050'],
        'long_video'  => ['label' => 'Long Video',  'color' => '#1A4A7A'],
        'photo'       => ['label' => 'Photo',        'color' => '#0d9488'],
        'story'       => ['label' => 'Story',         'color' => '#f59e0b'],
    ];

    /**
     * The full insight bundle for a client over a rolling window of days.
     * Cached for 30 minutes so the live API calls aren't repeated on every tab.
     *
     * @return array{items: array<int,array>, live: array<string,bool>, window_days:int, from:Carbon, to:Carbon}
     */
    public function forClient(Client $client, int $days = 90, bool $fresh = false): array
    {
        $key = "content_insights:{$client->id}:{$days}";

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(30), function () use ($client, $days) {
            $to   = now();
            $from = now()->subDays($days)->startOfDay();

            // 1) Local backbone — always available, keyed by a normalized url so we
            //    can merge live metrics onto the same physical post when possible.
            $items = $this->localItems($client, $from, $to);

            $live = [
                'instagram' => false,
                'youtube'   => false,
                'facebook'  => false,
            ];

            // 2) Best-effort live enrichment.
            try {
                $igItems = $this->instagramItems($client, $from, $to);
                if ($igItems !== null) {
                    $live['instagram'] = true;
                    $items = $this->merge($items, $igItems);
                }
            } catch (\Throwable $e) {
                Log::warning('IG insights failed', ['client' => $client->id, 'e' => $e->getMessage()]);
            }

            try {
                $ytEnriched = $this->enrichYouTube($client, $items);
                if ($ytEnriched !== null) {
                    $live['youtube'] = true;
                    $items = $ytEnriched;
                }
            } catch (\Throwable $e) {
                Log::warning('YT insights failed', ['client' => $client->id, 'e' => $e->getMessage()]);
            }

            // Stable ordering: newest first.
            usort($items, fn ($a, $b) => $b['published_at']->timestamp <=> $a['published_at']->timestamp);

            return [
                'items'       => $items,
                'live'        => $live,
                'window_days' => $days,
                'from'        => $from,
                'to'          => $to,
            ];
        });
    }

    /** Convenience: just the items array. */
    public function items(Client $client, int $days = 90): array
    {
        return $this->forClient($client, $days)['items'];
    }

    /**
     * Local published posts → normalized items. The best-scoring attempt supplies
     * the caption. These never carry live metrics by themselves.
     */
    private function localItems(Client $client, Carbon $from, Carbon $to): array
    {
        $posts = Post::where('client_id', $client->id)
            ->whereIn('publish_status', ['published', 'dry_run'])
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$from, $to])
            ->with('attempts')
            ->get();

        return $posts->map(function (Post $p) {
            $attempt = $p->attempts->sortByDesc('score')->first();
            $platform = config('publishing.platforms.' . $p->scope, 'social');

            return $this->normalize([
                'platform'     => $platform,
                'format'       => $p->post_type,
                'title'        => $p->keyword ?: ('Post #' . $p->id),
                'caption'      => $attempt->caption ?? '',
                'url'          => $p->external_url,
                'published_at' => $p->published_at,
                'score'        => (int) $p->best_score,
                'post_id'      => $p->id,
            ]);
        })->all();
    }

    /**
     * Instagram media + per-media insights (best-effort).
     * Returns null when IG isn't connected so the caller leaves `live` false.
     */
    private function instagramItems(Client $client, Carbon $from, Carbon $to): ?array
    {
        $token = $client->ig_access_token ?: config('services.instagram.token');
        $igId  = $client->ig_business_id  ?: config('services.instagram.business_id');

        if (! $token || ! $igId) {
            return null;
        }

        $media = Http::timeout(20)->get(self::IG_BASE . "/{$igId}/media", [
            'fields'       => 'id,caption,media_type,media_product_type,permalink,timestamp,like_count,comments_count',
            'limit'        => 50,
            'access_token' => $token,
        ]);

        if (! $media->ok()) {
            Log::warning('IG media list failed', ['client' => $client->id, 'body' => $media->json('error.message')]);
            return null;
        }

        $out = [];
        foreach ($media->json('data', []) as $m) {
            $ts = isset($m['timestamp']) ? Carbon::parse($m['timestamp']) : null;
            if (! $ts || ! $ts->between($from, $to)) {
                continue;
            }

            $format  = $this->igFormat($m);
            $insights = $this->igMediaInsights($m['id'], $format, $token);

            $out[] = $this->normalize([
                'platform'     => 'instagram',
                'format'       => $format,
                'title'        => mb_substr($m['caption'] ?? 'Instagram post', 0, 60),
                'caption'      => $m['caption'] ?? '',
                'url'          => $m['permalink'] ?? null,
                'published_at' => $ts,
                'likes'        => (int) ($m['like_count'] ?? 0),
                'comments'     => (int) ($m['comments_count'] ?? 0),
                'reach'        => $insights['reach'] ?? null,
                'saves'        => $insights['saved'] ?? null,
                'shares'       => $insights['shares'] ?? null,
                'views'        => $insights['views'] ?? null,
                'plays'        => $insights['views'] ?? null, // 'plays' retired by Meta (Apr 2025); 'views' is its successor
                'live'         => true,
            ]);
        }

        return $out;
    }

    /** Pull the handful of engagement metrics IG exposes per media object. */
    private function igMediaInsights(string $mediaId, string $format, string $token): array
    {
        // Meta unified impressions/plays/video_views into a single 'views' metric
        // across ALL media types in April 2025 and retired 'plays'. Requesting the
        // old 'plays' metric for reels caused the *entire* insights call to be
        // rejected (so reach/saved/shares came back empty too) — use 'views' for
        // every format instead.
        $metrics = 'reach,saved,shares,views';

        try {
            $res = Http::timeout(15)->get(self::IG_BASE . "/{$mediaId}/insights", [
                'metric'       => $metrics,
                'access_token' => $token,
            ]);
            if (! $res->ok()) {
                // Surface WHY — almost always a missing `instagram_manage_insights`
                // permission. Silent [] used to make this impossible to diagnose.
                Log::warning('IG media insights failed', [
                    'media'  => $mediaId,
                    'metric' => $metrics,
                    'error'  => $res->json('error.message') ?? $res->body(),
                ]);
                return [];
            }
            $vals = [];
            foreach ($res->json('data', []) as $row) {
                $vals[$row['name']] = (int) ($row['values'][0]['value'] ?? 0);
            }
            return $vals;
        } catch (\Throwable) {
            return [];
        }
    }

    private function igFormat(array $m): string
    {
        $product = strtoupper($m['media_product_type'] ?? '');
        $type    = strtoupper($m['media_type'] ?? '');

        return match (true) {
            $product === 'REELS'     => 'reels',
            $product === 'STORY'     => 'story',
            $type === 'VIDEO'        => 'reels',
            default                  => 'photo',
        };
    }

    /**
     * Enrich existing YouTube items with real view/like/comment statistics via the
     * Data API. Returns the (possibly mutated) full item list, or null when YT
     * isn't usable (no API key, or no YT videos to enrich).
     */
    private function enrichYouTube(Client $client, array $items): ?array
    {
        $key = config('services.youtube.key');
        if (! $key) {
            return null;
        }

        // Collect YouTube video ids from item urls.
        $idByIndex = [];
        foreach ($items as $i => $it) {
            if ($it['platform'] !== 'youtube' || ! $it['url']) {
                continue;
            }
            $vid = $this->youtubeId($it['url']);
            if ($vid) {
                $idByIndex[$i] = $vid;
            }
        }

        if (empty($idByIndex)) {
            return null;
        }

        $stats = [];
        foreach (array_chunk(array_values($idByIndex), 50) as $chunk) {
            $res = Http::timeout(15)->get(self::YT_BASE . '/videos', [
                'part' => 'statistics',
                'id'   => implode(',', $chunk),
                'key'  => $key,
            ]);
            if (! $res->ok()) {
                continue;
            }
            foreach ($res->json('items', []) as $v) {
                $stats[$v['id']] = $v['statistics'] ?? [];
            }
        }

        if (empty($stats)) {
            return null;
        }

        foreach ($idByIndex as $i => $vid) {
            if (! isset($stats[$vid])) {
                continue;
            }
            $s = $stats[$vid];
            $items[$i]['plays']    = (int) ($s['viewCount'] ?? 0);
            $items[$i]['views']    = (int) ($s['viewCount'] ?? 0);
            $items[$i]['reach']    = (int) ($s['viewCount'] ?? 0); // views ≈ reach proxy on YT
            $items[$i]['likes']    = (int) ($s['likeCount'] ?? 0);
            $items[$i]['comments'] = (int) ($s['commentCount'] ?? 0);
            $items[$i]['live']     = true;
            $items[$i] = $this->recompute($items[$i]);
        }

        return $items;
    }

    private function youtubeId(string $url): ?string
    {
        if (preg_match('~(?:youtu\.be/|v=|/shorts/|/embed/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Merge live items into the local backbone. A live item whose url matches a
     * local item enriches it in place; otherwise it's appended as a standalone
     * item (e.g. organic posts that weren't published through this app).
     */
    private function merge(array $base, array $live): array
    {
        $index = [];
        foreach ($base as $i => $it) {
            if ($it['url']) {
                $index[$this->urlKey($it['url'])] = $i;
            }
        }

        foreach ($live as $liveItem) {
            $key = $liveItem['url'] ? $this->urlKey($liveItem['url']) : null;
            if ($key !== null && isset($index[$key])) {
                $i = $index[$key];
                // Keep the local AI score, overlay real metrics.
                foreach (['reach', 'impressions', 'saves', 'shares', 'likes', 'comments', 'plays', 'views', 'watch_time'] as $f) {
                    if ($liveItem[$f] !== null) {
                        $base[$i][$f] = $liveItem[$f];
                    }
                }
                $base[$i]['live'] = true;
                $base[$i] = $this->recompute($base[$i]);
            } else {
                $base[] = $liveItem;
            }
        }

        return $base;
    }

    private function urlKey(string $url): string
    {
        return rtrim(strtolower(preg_replace('~^https?://(www\.)?~', '', $url)), '/');
    }

    /**
     * Build a complete, well-typed item from a sparse input array.
     */
    private function normalize(array $in): array
    {
        $format = $in['format'] ?? 'photo';
        $meta   = self::FORMATS[$format] ?? ['label' => ucfirst(str_replace('_', ' ', $format)), 'color' => '#64748b'];

        $item = [
            'platform'     => $in['platform'] ?? 'social',
            'format'       => $format,
            'format_label' => $meta['label'],
            'format_color' => $meta['color'],
            'title'        => trim((string) ($in['title'] ?? '')) ?: 'Untitled',
            'caption'      => (string) ($in['caption'] ?? ''),
            'url'          => $in['url'] ?? null,
            'published_at' => $in['published_at'] instanceof Carbon ? $in['published_at'] : Carbon::parse($in['published_at']),
            'score'        => isset($in['score']) ? (int) $in['score'] : null,
            'reach'        => $in['reach']       ?? null,
            'impressions'  => $in['impressions'] ?? null,
            'saves'        => $in['saves']       ?? null,
            'shares'       => $in['shares']      ?? null,
            'likes'        => $in['likes']       ?? null,
            'comments'     => $in['comments']    ?? null,
            'plays'        => $in['plays']       ?? null,
            'views'        => $in['views']       ?? null,
            'watch_time'   => $in['watch_time']  ?? null,
            'post_id'      => $in['post_id']     ?? null,
            'live'         => (bool) ($in['live'] ?? false),
        ];

        return $this->recompute($item);
    }

    /** (Re)derive engagement + engagement-rate after metrics change. */
    private function recompute(array $item): array
    {
        $hasEng = $item['likes'] !== null || $item['comments'] !== null
            || $item['saves'] !== null || $item['shares'] !== null;

        $engagement = $hasEng
            ? (int) (($item['likes'] ?? 0) + ($item['comments'] ?? 0) + ($item['saves'] ?? 0) + ($item['shares'] ?? 0))
            : null;

        $reach = $item['reach'] ?? $item['views'] ?? $item['plays'] ?? null;
        $rate  = ($engagement !== null && $reach) ? round($engagement / max(1, $reach) * 100, 2) : null;

        $item['engagement'] = $engagement;
        $item['eng_rate']   = $rate;

        return $item;
    }

    /**
     * Pick the best numeric ranking metric for an item, honouring a preference
     * order. Always returns a number so lists sort deterministically (falls back
     * to the AI score, then 0).
     */
    public static function metric(array $item, string $metric): float
    {
        $direct = $item[$metric] ?? null;
        if (is_numeric($direct)) {
            return (float) $direct;
        }
        // Sensible fallbacks so a column is never empty-sorted.
        return (float) ($item['score'] ?? 0);
    }

    /** True if ANY item in the set carries a live value for the metric. */
    public static function hasMetric(array $items, string $metric): bool
    {
        foreach ($items as $it) {
            if (isset($it[$metric]) && is_numeric($it[$metric])) {
                return true;
            }
        }
        return false;
    }
}