<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Post;
use App\Models\PostAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Publishes a Post to YouTube via Data API v3.
 *
 * Token priority: client.yt_access_token > env YT_ACCESS_TOKEN.
 * If neither set → dry-run mode.
 *
 * Flow:
 *   1) (If access token expired/missing AND refresh_token available) → refresh
 *   2) POST videos.insert (resumable upload)
 *      - Step a: start session, get upload URL
 *      - Step b: PUT bytes
 *      - Returns: video ID
 *   3) Build watch URL
 */
class YoutubePublishService
{
    private const UPLOAD_URL = 'https://www.googleapis.com/upload/youtube/v3/videos';
    private const OAUTH_URL  = 'https://oauth2.googleapis.com/token';

    public function publish(Post $post, PostAttempt $winner): array
    {
        $client = $post->client;
        $token  = $this->resolveAccessToken($client);

        // ── DRY-RUN MODE ──
        if (! $token) {
            Log::info('YoutubePublishService: DRY-RUN (no tokens configured)', [
                'post_id' => $post->id,
                'title'   => mb_substr((string) $winner->caption, 0, 100),
                'media'   => $winner->file_path,
            ]);
            return [
                'success'  => true,
                'dry_run'  => true,
                'message'  => 'Dry run — YouTube OAuth not configured. No actual publish happened.',
            ];
        }

        $absolutePath = Storage::disk('public')->path($winner->file_path);
        if (! is_file($absolutePath)) {
            return ['success' => false, 'error' => "File missing on disk: {$winner->file_path}"];
        }

        $size  = filesize($absolutePath);
        $mime  = $winner->mime ?: 'video/mp4';

        // YouTube requires a TITLE — derive from caption (≤100 chars) or fallback
        $title = trim((string) $winner->caption);
        $title = $title !== '' ? mb_substr($title, 0, 100) : ("Post #{$post->id} — " . ucfirst($post->keyword));
        $description = trim(($winner->caption ?? '') . "\n\n" . ($winner->hashtags ?? ''));

        $tags = [];
        preg_match_all('/#(\w+)/u', (string) $winner->hashtags, $m);
        $tags = array_slice($m[1] ?? [], 0, 30);

        $metadata = [
            'snippet' => [
                'title'       => $title,
                'description' => $description,
                'tags'        => $tags,
                'categoryId'  => '22', // People & Blogs (safe default)
            ],
            'status' => [
                'privacyStatus' => 'public',
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        try {
            // Step 1: start resumable upload session
            $start = Http::timeout(30)->withHeaders([
                'Authorization'             => 'Bearer ' . $token,
                'Content-Type'              => 'application/json; charset=UTF-8',
                'X-Upload-Content-Length'   => (string) $size,
                'X-Upload-Content-Type'     => $mime,
            ])->post(self::UPLOAD_URL . '?uploadType=resumable&part=snippet,status', $metadata);

            if (! $start->successful()) {
                return ['success' => false, 'error' => 'YT init: ' . $start->body()];
            }
            $uploadUrl = $start->header('Location');
            if (! $uploadUrl) {
                return ['success' => false, 'error' => 'YT did not return upload URL'];
            }

            // Step 2: upload the bytes
            $upload = Http::timeout(600)->withHeaders([
                'Content-Type'   => $mime,
                'Content-Length' => (string) $size,
            ])->withBody(file_get_contents($absolutePath), $mime)->put($uploadUrl);

            if (! $upload->successful()) {
                return ['success' => false, 'error' => 'YT upload: ' . $upload->body()];
            }

            $videoId = $upload->json('id');
            if (! $videoId) {
                return ['success' => false, 'error' => 'YT upload returned no video ID'];
            }

            return [
                'success'          => true,
                'external_post_id' => $videoId,
                'external_url'     => 'https://www.youtube.com/watch?v=' . $videoId,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Return a valid access token, refreshing if expired.
     */
    private function resolveAccessToken(?Client $client): ?string
    {
        if (! $client) return null;

        // 1. Per-client token (preferred)
        if ($client->yt_access_token) {
            $exp = $client->yt_token_expires_at;
            if (! $exp || Carbon::parse($exp)->isFuture()) {
                return $client->yt_access_token;
            }
            // expired → refresh
            if ($client->yt_refresh_token) {
                $refreshed = $this->refreshAccessToken($client);
                if ($refreshed) return $refreshed;
            }
        }

        // 2. Just refresh token (no access token cached)
        if ($client->yt_refresh_token) {
            return $this->refreshAccessToken($client);
        }

        // 3. Global fallback (single-account testing)
        return config('services.google.yt_access_token') ?: null;
    }

    private function refreshAccessToken(Client $client): ?string
    {
        $googleClientId     = (string) config('services.google.client_id');
        $googleClientSecret = (string) config('services.google.client_secret');
        if ($googleClientId === '' || $googleClientSecret === '') {
            Log::warning('YT refresh: Google client_id/secret not configured');
            return null;
        }

        try {
            $res = Http::asForm()->timeout(20)->post(self::OAUTH_URL, [
                'client_id'     => $googleClientId,
                'client_secret' => $googleClientSecret,
                'refresh_token' => $client->yt_refresh_token,
                'grant_type'    => 'refresh_token',
            ]);

            if (! $res->ok()) {
                Log::warning('YT refresh failed', ['body' => $res->body()]);
                return null;
            }

            $access = $res->json('access_token');
            $exp    = (int) $res->json('expires_in', 3600);
            $client->update([
                'yt_access_token'     => $access,
                'yt_token_expires_at' => now()->addSeconds($exp - 60),
            ]);
            return $access;
        } catch (\Throwable $e) {
            Log::warning('YT refresh exception', ['e' => $e->getMessage()]);
            return null;
        }
    }
}
