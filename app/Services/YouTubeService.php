<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class YouTubeService
{
    private string $key;

    public function __construct()
    {
        $this->key = (string) config('services.youtube.key');
    }

    /**
     * Search trending videos for a keyword.
     *
     * Strategy:
     *  - Restrict to the last `freshness_days` days (default 30) so we get recent uploads.
     *  - Order by viewCount, then re-sort locally by views/day to surface true momentum.
     *  - For "short_video": strictly enforce duration <= 60s using contentDetails.duration.
     *
     * @param  string  $keyword
     * @param  string  $postType  long_video|short_video
     * @param  int     $limit
     * @param  int     $freshnessDays  how far back to search
     */
    public function trending(string $keyword, string $postType, int $limit = 10, int $freshnessDays = 30): array
    {
        if ($this->key === '') {
            return ['success' => false, 'error' => 'YouTube API key not configured. Set YOUTUBE_API_KEY in .env.'];
        }

        $duration = match ($postType) {
            'short_video' => 'short',
            'long_video'  => 'long',
            default       => 'medium',
        };

        try {
            $search = Http::timeout(20)->get('https://www.googleapis.com/youtube/v3/search', [
                'key'               => $this->key,
                'part'              => 'snippet',
                'q'                 => $keyword,
                'type'              => 'video',
                'order'             => 'viewCount',
                'maxResults'        => 50,                // pull more, filter down
                'videoDuration'     => $duration,
                'publishedAfter'    => Carbon::now()->subDays($freshnessDays)->toIso8601ZuluString(),
                'safeSearch'        => 'moderate',
                'relevanceLanguage' => 'en',
            ]);

            if (! $search->ok()) {
                return ['success' => false, 'error' => 'YouTube search failed: ' . $search->json('error.message', 'unknown')];
            }

            $ids = collect($search->json('items', []))
                ->pluck('id.videoId')
                ->filter()
                ->values();

            if ($ids->isEmpty()) {
                return ['success' => true, 'items' => [], 'note' => "No videos found in last {$freshnessDays} days."];
            }

            $details = Http::timeout(20)->get('https://www.googleapis.com/youtube/v3/videos', [
                'key'  => $this->key,
                'part' => 'snippet,statistics,contentDetails',
                'id'   => $ids->implode(','),
            ]);

            if (! $details->ok()) {
                return ['success' => false, 'error' => 'YouTube details failed: ' . $details->json('error.message', 'unknown')];
            }

            $items = collect($details->json('items', []))
                ->map(function ($v) {
                    $desc        = $v['snippet']['description'] ?? '';
                    $title       = $v['snippet']['title'] ?? '';
                    preg_match_all('/#\w+/u', $desc . ' ' . $title, $tags);

                    $publishedAt = $v['snippet']['publishedAt'] ?? null;
                    $duration    = $v['contentDetails']['duration'] ?? null;
                    $durationSec = $this->isoDurationToSeconds($duration);

                    $views   = (int) ($v['statistics']['viewCount'] ?? 0);
                    $daysOld = $publishedAt
                        ? max(1, Carbon::parse($publishedAt)->diffInDays(Carbon::now()))
                        : 1;

                    return [
                        'ref_id'         => $v['id'],
                        'title'          => $title,
                        'description'    => mb_substr($desc, 0, 500),
                        'channel'        => $v['snippet']['channelTitle'] ?? '',
                        'thumbnail'      => $v['snippet']['thumbnails']['high']['url']
                                            ?? $v['snippet']['thumbnails']['default']['url']
                                            ?? null,
                        'views'          => $views,
                        'likes'          => (int) ($v['statistics']['likeCount'] ?? 0),
                        'comments'       => (int) ($v['statistics']['commentCount'] ?? 0),
                        'duration'       => $duration,
                        'duration_sec'   => $durationSec,
                        'days_old'       => (int) $daysOld,
                        'views_per_day'  => (int) round($views / $daysOld),
                        'hashtags'       => array_values(array_unique($tags[0] ?? [])),
                        'url'            => 'https://www.youtube.com/watch?v=' . $v['id'],
                        'published'      => $publishedAt,
                        'published_human'=> $publishedAt ? Carbon::parse($publishedAt)->diffForHumans() : null,
                    ];
                })
                // Strict Shorts filter: <= 60 seconds
                ->when($postType === 'short_video', fn ($c) => $c->filter(fn ($i) => $i['duration_sec'] && $i['duration_sec'] <= 60))
                // For long_video, require >= 4 minutes (the API "long" filter is >20m which is too strict)
                ->when($postType === 'long_video', fn ($c) => $c->filter(fn ($i) => $i['duration_sec'] && $i['duration_sec'] >= 240))
                // Re-sort by view velocity (true trending signal)
                ->sortByDesc('views_per_day')
                ->take($limit)
                ->values()
                ->all();

            return [
                'success'        => true,
                'items'          => $items,
                'freshness_days' => $freshnessDays,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Convert ISO 8601 duration (PT1M30S) to total seconds.
     */
    private function isoDurationToSeconds(?string $iso): ?int
    {
        if (! $iso) return null;
        try {
            $i = new \DateInterval($iso);
            return ($i->d * 86400) + ($i->h * 3600) + ($i->i * 60) + $i->s;
        } catch (\Throwable) {
            return null;
        }
    }
}
