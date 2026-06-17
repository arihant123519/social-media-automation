<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * #15 — Social Analytics Auto-Report.
 *
 * Backbone = local published-post data (always available). On top of that it
 * makes best-effort Instagram Graph + YouTube Data calls for live reach /
 * follower / engagement figures. When a platform isn't connected it returns a
 * `connected: false` section so the report still renders cleanly.
 */
class AnalyticsService
{
    private const IG_BASE = 'https://graph.facebook.com/v18.0';
    private const YT_BASE = 'https://www.googleapis.com/youtube/v3';

    /**
     * Full monthly report for one client across every platform.
     */
    public function report(Client $client, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        return [
            'client'    => ['id' => $client->id, 'name' => $client->name, 'industry' => $client->industry],
            'month'     => $start->format('F Y'),
            'generated' => now()->format('d M Y, g:i A'),
            'local'     => $this->localStats($client, $start, $end),
            'instagram' => $this->instagram($client, $start, $end),
            'youtube'   => $this->youtube($client, $start, $end),
        ];
    }

    /**
     * DB-derived stats — never fails, works without any API token.
     */
    private function localStats(Client $client, Carbon $start, Carbon $end): array
    {
        $published = Post::where('client_id', $client->id)
            ->whereIn('publish_status', ['published', 'dry_run'])
            ->whereBetween('published_at', [$start, $end])
            ->get();

        $byPlatform = [];
        foreach (config('publishing.platforms', []) as $scope => $key) {
            $byPlatform[$key] = $published->where('scope', $scope)->count();
        }

        $top = $published->sortByDesc('best_score')->take(5)->map(fn ($p) => [
            'id'       => $p->id,
            'platform' => config('publishing.platforms.' . $p->scope, 'social'),
            'score'    => $p->best_score,
            'url'      => $p->external_url,
            'date'     => $p->published_at?->format('d M'),
        ])->values();

        // 6-month publishing trend for the chart
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $start->copy()->subMonths($i);
            $trend[] = [
                'label' => $m->format('M'),
                'count' => Post::where('client_id', $client->id)
                    ->whereIn('publish_status', ['published', 'dry_run'])
                    ->whereYear('published_at', $m->year)
                    ->whereMonth('published_at', $m->month)
                    ->count(),
            ];
        }

        return [
            'published_total' => $published->count(),
            'avg_score'       => round((float) $published->avg('best_score'), 1),
            'by_platform'     => $byPlatform,
            'top_posts'       => $top,
            'trend'           => $trend,
        ];
    }

    /**
     * Instagram reach / followers / engagement (best-effort).
     */
    private function instagram(Client $client, Carbon $start, Carbon $end): array
    {
        $token = $client->ig_access_token ?: config('services.instagram.token');
        $igId  = $client->ig_business_id  ?: config('services.instagram.business_id');

        if (! $token || ! $igId) {
            return ['connected' => false, 'reason' => 'Instagram not connected for this client.'];
        }

        try {
            $profile = Http::timeout(15)->get(self::IG_BASE . "/{$igId}", [
                'fields'       => 'followers_count,media_count',
                'access_token' => $token,
            ]);

            $reachRes = Http::timeout(15)->get(self::IG_BASE . "/{$igId}/insights", [
                'metric'       => 'reach',
                'period'       => 'days_28',
                'access_token' => $token,
            ]);

            $media = Http::timeout(15)->get(self::IG_BASE . "/{$igId}/media", [
                'fields'       => 'permalink,like_count,comments_count,caption,timestamp',
                'limit'        => 30,
                'access_token' => $token,
            ]);

            if (! $profile->ok()) {
                return ['connected' => false, 'reason' => 'IG API error: ' . ($profile->json('error.message') ?? 'unknown')];
            }

            $followers = (int) $profile->json('followers_count', 0);
            $reach     = (int) $reachRes->json('data.0.values.0.value', 0);

            $items = collect($media->json('data', []))->filter(function ($m) use ($start, $end) {
                $ts = isset($m['timestamp']) ? Carbon::parse($m['timestamp']) : null;
                return $ts && $ts->between($start, $end);
            });

            $likes    = (int) $items->sum('like_count');
            $comments = (int) $items->sum('comments_count');
            $posts    = $items->count();
            $engRate  = $followers > 0 && $posts > 0
                ? round((($likes + $comments) / $posts) / $followers * 100, 2)
                : 0.0;

            $topPosts = $items->sortByDesc(fn ($m) => ($m['like_count'] ?? 0) + ($m['comments_count'] ?? 0))
                ->take(5)
                ->map(fn ($m) => [
                    'caption'  => mb_substr($m['caption'] ?? '', 0, 60),
                    'likes'    => (int) ($m['like_count'] ?? 0),
                    'comments' => (int) ($m['comments_count'] ?? 0),
                    'url'      => $m['permalink'] ?? null,
                ])->values();

            return [
                'connected'       => true,
                'followers'       => $followers,
                'reach'           => $reach,
                'engagement_rate' => $engRate,
                'posts_in_month'  => $posts,
                'likes'           => $likes,
                'comments'        => $comments,
                'top_posts'       => $topPosts,
            ];
        } catch (\Throwable $e) {
            Log::warning('IG analytics failed', ['client' => $client->id, 'e' => $e->getMessage()]);
            return ['connected' => false, 'reason' => 'IG analytics error: ' . $e->getMessage()];
        }
    }

    /**
     * YouTube channel stats (best-effort, via API key + channel id).
     */
    private function youtube(Client $client, Carbon $start, Carbon $end): array
    {
        $key       = config('services.youtube.key');
        $channelId = $client->yt_channel_id;

        if (! $key || ! $channelId) {
            return ['connected' => false, 'reason' => 'YouTube not connected for this client.'];
        }

        try {
            $res = Http::timeout(15)->get(self::YT_BASE . '/channels', [
                'part' => 'statistics',
                'id'   => $channelId,
                'key'  => $key,
            ]);
            if (! $res->ok()) {
                return ['connected' => false, 'reason' => 'YouTube API error.'];
            }
            $stats = $res->json('items.0.statistics', []);

            return [
                'connected'    => true,
                'subscribers'  => (int) ($stats['subscriberCount'] ?? 0),
                'total_views'  => (int) ($stats['viewCount'] ?? 0),
                'total_videos' => (int) ($stats['videoCount'] ?? 0),
            ];
        } catch (\Throwable $e) {
            Log::warning('YT analytics failed', ['client' => $client->id, 'e' => $e->getMessage()]);
            return ['connected' => false, 'reason' => 'YouTube analytics error: ' . $e->getMessage()];
        }
    }
}
