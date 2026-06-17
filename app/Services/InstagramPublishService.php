<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Post;
use App\Models\PostAttempt;
use App\Services\VideoNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Publishes a Post to Instagram via Graph API.
 *
 * Token priority: client.ig_access_token > env IG_ACCESS_TOKEN.
 * If neither set → dry-run mode (logs + returns success without calling IG).
 *
 * Flow per Meta docs:
 *   1) POST /{ig-user-id}/media          → returns container_id
 *   2) GET  /{container_id}              → poll until status_code == FINISHED
 *   3) POST /{ig-user-id}/media_publish  → returns live post id
 */
class InstagramPublishService
{
    private const BASE = 'https://graph.facebook.com/v18.0';
    // Resumable upload endpoint (binary file upload, no public URL required).
    private const RUPLOAD = 'https://rupload.facebook.com/ig-api-upload/v18.0';

    public function publish(Post $post, PostAttempt $winner): array
    {
        $client = $post->client;
        $token  = $client?->ig_access_token ?: config('services.instagram.token');
        $igId   = $client?->ig_business_id  ?: config('services.instagram.business_id');

        // ── DRY-RUN MODE ──
        if (! $token || ! $igId) {
            Log::info('InstagramPublishService: DRY-RUN (no tokens configured)', [
                'post_id'  => $post->id,
                'caption'  => mb_substr((string) $winner->caption, 0, 100),
                'hashtags' => $winner->hashtags,
                'media'    => $winner->file_path,
            ]);
            return [
                'success'  => true,
                'dry_run'  => true,
                'message'  => 'Dry run — IG tokens not configured. No actual publish happened.',
            ];
        }

        $isVideo = str_starts_with((string) $winner->mime, 'video/');
        $caption = trim(($winner->caption ?? '') . "\n\n" . ($winner->hashtags ?? ''));
        $mediaType = match ($post->post_type) {
            'reels' => 'REELS',
            'story' => 'STORIES',
            default => $isVideo ? 'VIDEO' : 'IMAGE',
        };

        // ── #6 Quota check (Meta: 25 posts / 24h per account) ──
        $quota = $this->checkQuota($igId, $token);
        if (! $quota['ok']) {
            return ['success' => false, 'error' => $quota['error']];
        }

        try {
            // ── Re-encode videos to a Reels-compliant MP4 (fixes 2207076 etc.) ──
            // Returns the relative path to use for upload (normalized if it worked,
            // otherwise the original). Both upload paths below read this.
            $uploadRel = $isVideo ? $this->normalizedVideoPath($winner) : (string) $winner->file_path;

            // ── Videos → Resumable Upload (push bytes directly, no public URL) ──
            // Instagram's URL-fetch path requires it to download `video_url` from
            // ITS OWN servers. A localhost / private APP_URL is unreachable from
            // Meta, which surfaces as error 2207076 ("Media upload has failed").
            // So we use the Resumable Upload API whenever the media URL isn't
            // publicly reachable, and always for large files.
            if ($isVideo) {
                $abs       = Storage::disk('public')->path($uploadRel);
                $sizeMb    = is_file($abs) ? filesize($abs) / 1048576 : 0;
                $threshold = (int) config('services.instagram.resumable_threshold_mb', 100);
                $reachable = $this->isPubliclyReachable($this->publicMediaUrl($uploadRel));
                if ($sizeMb > $threshold || ! $reachable) {
                    return $this->publishVideoResumable($uploadRel, $igId, $token, $mediaType, $caption);
                }
            }

            // ── URL-fetch path (images + small videos) ──
            // Instagram only accepts JPEG for image_url — convert PNG/WebP/etc.
            // Videos use the Reels-normalized MP4 produced above.
            $mediaRel  = $isVideo ? $uploadRel : $this->ensureJpegForImage($winner);
            $publicUrl = $this->publicMediaUrl($mediaRel);
            if (! str_starts_with($publicUrl, 'http')) {
                return ['success' => false, 'error' => "Media URL is not public: {$publicUrl}. Configure APP_URL with a publicly reachable hostname (use ngrok for local dev)."];
            }

            $createPayload = [
                'caption'      => $caption,
                'access_token' => $token,
            ];
            if ($isVideo) {
                $createPayload['video_url']  = $publicUrl;
                $createPayload['media_type'] = $mediaType;
            } else {
                $createPayload['image_url'] = $publicUrl;
            }

            $create = Http::timeout(60)->post(self::BASE . "/{$igId}/media", $createPayload);
            if (! $create->ok()) {
                return ['success' => false, 'error' => 'IG create: ' . ($create->json('error.message') ?? $create->body())];
            }
            $containerId = $create->json('id');

            if ($isVideo) {
                $polled = $this->waitUntilFinished($containerId, $token);
                if (! $polled['ready']) {
                    return ['success' => false, 'error' => $this->processError($polled, $publicUrl)];
                }
            }

            return $this->finalizePublish($igId, $containerId, $token);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Re-encode the winning attempt's video into a Reels-compliant MP4 and return
     * its RELATIVE path (under the public disk). Falls back to the original file's
     * relative path if ffmpeg is unavailable or the encode fails — so a missing
     * ffmpeg never blocks publishing, it just loses the auto-fix.
     */
    private function normalizedVideoPath(PostAttempt $winner): string
    {
        $rel = (string) $winner->file_path;
        $srcAbs = Storage::disk('public')->path($rel);

        // Write the normalized copy next to the original: foo.mp4 → foo_ig.mp4
        $dstRel = preg_replace('/\.[^.]+$/', '', $rel) . '_ig.mp4';
        $dstAbs = Storage::disk('public')->path($dstRel);

        $out = app(VideoNormalizer::class)->toReels($srcAbs, $dstAbs);

        return $out ? $dstRel : $rel;
    }

    /**
     * #4 — Publish a large video via the Resumable Upload API.
     * Uploads the local file bytes directly to Meta (no public URL / tunnel needed).
     */
    private function publishVideoResumable(string $rel, string $igId, string $token, string $mediaType, string $caption): array
    {
        $abs = Storage::disk('public')->path($rel);
        if (! is_file($abs)) {
            return ['success' => false, 'error' => "Video file not found for resumable upload: {$rel}"];
        }
        $size = filesize($abs);

        // Step 1: create a resumable container → returns container id + upload uri
        $create = Http::timeout(60)->post(self::BASE . "/{$igId}/media", [
            'media_type'   => $mediaType === 'IMAGE' ? 'VIDEO' : $mediaType,
            'upload_type'  => 'resumable',
            'caption'      => $caption,
            'access_token' => $token,
        ]);
        if (! $create->ok()) {
            return ['success' => false, 'error' => 'IG resumable create: ' . ($create->json('error.message') ?? $create->body())];
        }
        $containerId = $create->json('id');
        $uploadUri   = $create->json('uri') ?: (self::RUPLOAD . "/{$containerId}");

        // Step 2: upload the raw bytes (single shot, offset 0)
        $upload = Http::withHeaders([
            'Authorization' => 'OAuth ' . $token,
            'offset'        => '0',
            'file_size'     => (string) $size,
        ])->withBody(file_get_contents($abs), 'application/octet-stream')
          ->timeout(600)
          ->post($uploadUri);

        if (! $upload->successful()) {
            return ['success' => false, 'error' => 'IG resumable upload: ' . $upload->body()];
        }

        // Step 3: wait for processing, then publish
        $polled = $this->waitUntilFinished($containerId, $token);
        if (! $polled['ready']) {
            return ['success' => false, 'error' => $this->processError($polled, '(resumable upload)')];
        }

        return $this->finalizePublish($igId, $containerId, $token);
    }

    /**
     * Final media_publish call + permalink fetch, shared by both upload paths.
     */
    private function finalizePublish(string $igId, string $containerId, string $token): array
    {
        $publish = Http::timeout(30)->post(self::BASE . "/{$igId}/media_publish", [
            'creation_id'  => $containerId,
            'access_token' => $token,
        ]);
        if (! $publish->ok()) {
            return ['success' => false, 'error' => 'IG publish: ' . ($publish->json('error.message') ?? $publish->body())];
        }

        $liveId = $publish->json('id');
        return [
            'success'          => true,
            'external_post_id' => $liveId,
            'external_url'     => $this->fetchPermalink($liveId, $token),
        ];
    }

    /**
     * #6 — Check Meta's content publishing quota (25/24h per account).
     * Fail-open: if the limit endpoint errors, we don't block the publish.
     *
     * @return array{ok: bool, error?: string, used?: int, total?: int}
     */
    private function checkQuota(string $igId, string $token): array
    {
        try {
            $res = Http::timeout(15)->get(self::BASE . "/{$igId}/content_publishing_limit", [
                'fields'       => 'quota_usage,config',
                'access_token' => $token,
            ]);
            if (! $res->ok()) {
                Log::warning('IG quota check failed', ['body' => $res->body()]);
                return ['ok' => true];
            }
            $row   = $res->json('data.0', []);
            $used  = (int) ($row['quota_usage'] ?? 0);
            $total = (int) ($row['config']['quota_total'] ?? config('services.instagram.publish_limit', 25));
            if ($used >= $total) {
                return ['ok' => false, 'error' => "Instagram publishing limit reached ({$used}/{$total} in last 24h). Try again later."];
            }
            return ['ok' => true, 'used' => $used, 'total' => $total];
        } catch (\Throwable $e) {
            Log::warning('IG quota check exception', ['e' => $e->getMessage()]);
            return ['ok' => true];
        }
    }

    /**
     * Can Instagram's servers actually fetch this URL? Returns false for
     * localhost / private-network / no-TLD hosts (typical of XAMPP local dev),
     * where IG's URL-fetch fails with 2207076 and we must use resumable upload.
     */
    private function isPubliclyReachable(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        // Obvious local hostnames.
        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.localhost')) {
            return false;
        }

        // Private / loopback / link-local IPv4 ranges (and ::1).
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        // A bare hostname with no dot (e.g. "mypc") is not internet-resolvable.
        return str_contains($host, '.');
    }

    private function publicMediaUrl(string $relativePath): string
    {
        // Storage::url() respects APP_URL (typically a full URL when deployed)
        $url = Storage::disk('public')->url($relativePath);

        // If APP_URL is just /storage/... (relative) we have to make it absolute
        if (! str_starts_with($url, 'http')) {
            $base = rtrim((string) config('app.url'), '/');
            $url  = $base . '/' . ltrim($url, '/');
        }
        return $url;
    }

    /**
     * Instagram's Content Publishing API only accepts JPEG for image_url.
     * Convert PNG/WebP/GIF/etc to a JPEG copy (white-flattened for transparency)
     * and return its relative path. Videos and existing JPEGs pass through unchanged.
     */
    private function ensureJpegForImage(PostAttempt $winner): string
    {
        $rel  = (string) $winner->file_path;
        $mime = (string) $winner->mime;

        if (! str_starts_with($mime, 'image/') || $mime === 'image/jpeg') {
            return $rel;   // video or already JPEG
        }

        $jpegRel = preg_replace('/\.[^.]+$/', '', $rel) . '_ig.jpg';
        $srcAbs  = Storage::disk('public')->path($rel);
        $jpegAbs = Storage::disk('public')->path($jpegRel);

        if (file_exists($jpegAbs)) {
            return $jpegRel;   // already converted
        }

        if (! function_exists('imagejpeg') || ! file_exists($srcAbs)) {
            Log::warning('IG image convert skipped (no GD or missing file)', ['path' => $rel]);
            return $rel;       // fall back — IG may still reject, but better than crashing
        }

        try {
            $img = match ($mime) {
                'image/png'  => @imagecreatefrompng($srcAbs),
                'image/webp' => @imagecreatefromwebp($srcAbs),
                'image/gif'  => @imagecreatefromgif($srcAbs),
                default      => @imagecreatefromstring(file_get_contents($srcAbs)),
            };
            if (! $img) {
                return $rel;
            }

            // Flatten any transparency onto white (JPEG has no alpha channel)
            $w = imagesx($img);
            $h = imagesy($img);
            $bg = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefilledrectangle($bg, 0, 0, $w, $h, $white);
            imagecopy($bg, $img, 0, 0, 0, 0, $w, $h);
            imagejpeg($bg, $jpegAbs, 90);
            imagedestroy($img);
            imagedestroy($bg);

            return file_exists($jpegAbs) ? $jpegRel : $rel;
        } catch (\Throwable $e) {
            Log::warning('IG image convert failed', ['path' => $rel, 'e' => $e->getMessage()]);
            return $rel;
        }
    }

    private function waitUntilFinished(string $containerId, string $token, int $maxSeconds = 120): array
    {
        $start  = time();
        $detail = '';
        while ((time() - $start) < $maxSeconds) {
            $res = Http::timeout(10)->get(self::BASE . "/{$containerId}", [
                // status = human-readable reason; error_message = the actual failure cause
                'fields'       => 'status_code,status',
                'access_token' => $token,
            ]);
            $code   = $res->json('status_code');
            $detail = (string) $res->json('status', '');

            if ($code === 'FINISHED') return ['ready' => true, 'status' => $code, 'detail' => $detail];
            if ($code === 'ERROR' || $code === 'EXPIRED') {
                Log::warning('IG container processing failed', [
                    'container' => $containerId,
                    'status'    => $detail,
                ]);
                return ['ready' => false, 'status' => $code, 'detail' => $detail];
            }
            sleep(3);
        }
        return ['ready' => false, 'status' => 'TIMEOUT', 'detail' => $detail];
    }

    /**
     * Turn a failed container poll into a human-actionable message. Instagram's
     * `status` text usually contains the real reason + an error code.
     */
    private function processError(array $polled, string $mediaUrl): string
    {
        $detail = trim((string) ($polled['detail'] ?? ''));
        $code   = $polled['status'] ?? 'ERROR';

        if ($code === 'TIMEOUT') {
            return 'Instagram took too long to process the video (timeout). Try a shorter/smaller MP4.';
        }

        // Common, recognisable causes mapped to plain advice.
        $hint = match (true) {
            str_contains($detail, '2207026'), str_contains($detail, '2207076')
                => ' Re-encode to a standard Reels MP4: H.264 video + AAC audio, constant 30fps, 1080x1920 (9:16), web-optimized (faststart).',
            str_contains($detail, '2207020'), str_contains($detail, '2207003')
                => ' Instagram could not fetch the media URL — make sure it is publicly reachable over HTTPS.',
            str_contains($detail, 'aspect')   => ' Aspect ratio not allowed — use 9:16 for Reels.',
            str_contains($detail, 'duration') => ' Duration out of range — Reels must be 3 seconds to 15 minutes.',
            default => '',
        };

        $base = $detail !== '' ? "Instagram could not process the video: {$detail}." : 'Instagram could not process the video.';
        return $base . $hint;
    }

    private function fetchPermalink(string $mediaId, string $token): ?string
    {
        try {
            $res = Http::timeout(10)->get(self::BASE . "/{$mediaId}", [
                'fields'       => 'permalink',
                'access_token' => $token,
            ]);
            return $res->json('permalink');
        } catch (\Throwable) {
            return null;
        }
    }
}
