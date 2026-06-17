<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Publishes a Post to a Facebook Page via Graph API.
 *
 * Token priority: client.fb_page_token > env FB_PAGE_ACCESS_TOKEN.
 * If neither set → dry-run mode (mirrors InstagramPublishService).
 *
 * Photos → POST /{page-id}/photos     (url + caption)
 * Videos → POST /{page-id}/videos     (file_url + description)
 */
class FacebookPublishService
{
    private const BASE = 'https://graph.facebook.com/v18.0';

    public function publish(Post $post, PostAttempt $winner): array
    {
        $client  = $post->client;
        $pageId  = $client?->fb_page_id    ?: config('services.facebook.page_id');
        $token   = $client?->fb_page_token ?: config('services.facebook.page_token');

        // ── DRY-RUN MODE ──
        if (! $pageId || ! $token) {
            Log::info('FacebookPublishService: DRY-RUN (no page token configured)', [
                'post_id' => $post->id,
                'caption' => mb_substr((string) $winner->caption, 0, 100),
            ]);
            return [
                'success' => true,
                'dry_run' => true,
                'message' => 'Dry run — Facebook Page token not configured. No actual publish happened.',
            ];
        }

        $message = trim(($winner->caption ?? '') . "\n\n" . ($winner->hashtags ?? ''));
        $isVideo = str_starts_with((string) $winner->mime, 'video/');

        try {
            $publicUrl = $this->publicMediaUrl((string) $winner->file_path);
            if (! str_starts_with($publicUrl, 'http')) {
                return ['success' => false, 'error' => "Media URL is not public: {$publicUrl}. Set a publicly reachable APP_URL."];
            }

            if ($isVideo) {
                $res = Http::timeout(120)->post(self::BASE . "/{$pageId}/videos", [
                    'file_url'     => $publicUrl,
                    'description'  => $message,
                    'access_token' => $token,
                ]);
                $idKey = 'id';
            } else {
                $res = Http::timeout(60)->post(self::BASE . "/{$pageId}/photos", [
                    'url'          => $publicUrl,
                    'caption'      => $message,
                    'access_token' => $token,
                ]);
                $idKey = 'post_id';
            }

            if (! $res->ok()) {
                return ['success' => false, 'error' => 'FB publish: ' . ($res->json('error.message') ?? $res->body())];
            }

            $externalId = $res->json($idKey) ?: $res->json('id');
            $url = $externalId ? 'https://www.facebook.com/' . $externalId : null;

            return [
                'success'          => true,
                'external_post_id' => $externalId,
                'external_url'     => $url,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function publicMediaUrl(string $relativePath): string
    {
        $url = Storage::disk('public')->url($relativePath);
        if (! str_starts_with($url, 'http')) {
            $url = rtrim((string) config('app.url'), '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }
}
