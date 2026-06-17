<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class InstagramService
{
    private ?string $token;
    private ?string $businessId;

    public function __construct()
    {
        $this->token      = config('services.instagram.token') ?: null;
        $this->businessId = config('services.instagram.business_id') ?: null;
    }

    public function isConfigured(): bool
    {
        return $this->token !== null && $this->businessId !== null;
    }

    /**
     * Search trending Instagram media for a keyword (hashtag).
     *
     * Uses IG Graph API hashtag_search → top_media if configured.
     * Falls back to AI-generated list (handled by caller) if not.
     *
     * @param  string  $keyword
     * @param  string  $postType  reels|photo|story
     * @param  int     $limit
     * @return array{success: bool, items?: array, error?: string, fallback?: bool}
     */
    public function trending(string $keyword, string $postType, int $limit = 10): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'fallback' => true, 'error' => 'Instagram not configured. Set IG_ACCESS_TOKEN + IG_BUSINESS_ID in .env.'];
        }

        $tag = trim(preg_replace('/\s+/', '', $keyword), '#');
        if ($tag === '') {
            return ['success' => false, 'error' => 'Keyword empty.'];
        }

        try {
            // Step 1: hashtag search → get hashtag ID
            $hashtagRes = Http::timeout(15)->get('https://graph.facebook.com/v18.0/ig_hashtag_search', [
                'user_id'      => $this->businessId,
                'q'            => $tag,
                'access_token' => $this->token,
            ]);

            // Detect "Instagram Public Content Access" permission error → fall back to AI
            $errMsg = (string) $hashtagRes->json('error.message', '');
            if (str_contains($errMsg, 'Instagram Public Content Access')
                || str_contains($errMsg, 'must be reviewed and approved')) {
                return [
                    'success'  => false,
                    'fallback' => true,
                    'error'    => 'IG hashtag search needs Meta App Review approval — using AI fallback.',
                ];
            }

            // Other API errors (invalid token, etc.)
            if (! $hashtagRes->ok()) {
                return [
                    'success'  => false,
                    'fallback' => true,
                    'error'    => 'IG API error: ' . ($errMsg ?: $hashtagRes->body()),
                ];
            }

            $hashtagId = $hashtagRes->json('data.0.id');
            if (! $hashtagId) {
                return ['success' => false, 'fallback' => true, 'error' => 'Hashtag not found: #' . $tag];
            }

            // Step 2: top_media for this hashtag
            $mediaRes = Http::timeout(20)->get("https://graph.facebook.com/v18.0/{$hashtagId}/top_media", [
                'user_id'      => $this->businessId,
                'fields'       => 'id,caption,media_type,media_url,permalink,thumbnail_url,like_count,comments_count,timestamp',
                'limit'        => $limit,
                'access_token' => $this->token,
            ]);

            if (! $mediaRes->ok()) {
                return ['success' => false, 'error' => 'IG top_media failed: ' . $mediaRes->json('error.message', 'unknown')];
            }

            $wantType = match ($postType) {
                'reels' => 'VIDEO',
                'photo' => 'IMAGE',
                'story' => 'IMAGE', // stories aren't searchable via hashtag — fall through
                default => null,
            };

            $items = collect($mediaRes->json('data', []))
                ->when($wantType, fn ($c) => $c->where('media_type', $wantType))
                ->take($limit)
                ->map(function ($m) {
                    preg_match_all('/#\w+/u', $m['caption'] ?? '', $tags);

                    return [
                        'ref_id'    => $m['id'],
                        'title'     => mb_substr($m['caption'] ?? '', 0, 80),
                        'caption'   => $m['caption'] ?? '',
                        'thumbnail' => $m['thumbnail_url'] ?? $m['media_url'] ?? null,
                        'media_url' => $m['media_url'] ?? null,
                        'likes'     => (int) ($m['like_count'] ?? 0),
                        'comments'  => (int) ($m['comments_count'] ?? 0),
                        'hashtags'  => array_values(array_unique($tags[0] ?? [])),
                        'url'       => $m['permalink'] ?? null,
                        'published' => $m['timestamp'] ?? null,
                        'media_type' => $m['media_type'] ?? 'IMAGE',
                    ];
                })->values()->all();

            return ['success' => true, 'items' => $items];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
