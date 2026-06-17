<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Publishes a Post to LinkedIn via the UGC Posts / Images API.
 *
 * Token priority: client.linkedin_token > env LINKEDIN_ACCESS_TOKEN.
 * Author URN: client.linkedin_author_urn > env LINKEDIN_AUTHOR_URN
 *   (e.g. "urn:li:organization:12345" or "urn:li:person:abc").
 * If token or author missing → dry-run mode.
 *
 * Flow (image post):
 *   1) registerUpload → asset URN + upload URL
 *   2) PUT image bytes to upload URL
 *   3) POST /ugcPosts referencing the asset
 * Text-only post skips steps 1-2.
 */
class LinkedInPublishService
{
    private const API = 'https://api.linkedin.com/v2';

    public function publish(Post $post, PostAttempt $winner): array
    {
        $client = $post->client;
        $token  = $client?->linkedin_token      ?: config('services.linkedin.access_token');
        $author = $client?->linkedin_author_urn ?: config('services.linkedin.author_urn');

        // ── DRY-RUN MODE ──
        if (! $token || ! $author) {
            Log::info('LinkedInPublishService: DRY-RUN (no token/author configured)', [
                'post_id' => $post->id,
                'caption' => mb_substr((string) $winner->caption, 0, 100),
            ]);
            return [
                'success' => true,
                'dry_run' => true,
                'message' => 'Dry run — LinkedIn token/author not configured. No actual publish happened.',
            ];
        }

        $text    = trim(($winner->caption ?? '') . "\n\n" . ($winner->hashtags ?? ''));
        $isImage = str_starts_with((string) $winner->mime, 'image/');

        try {
            $assetUrn = null;
            if ($isImage) {
                $assetUrn = $this->uploadImage($winner, $author, $token);
                if (! $assetUrn) {
                    return ['success' => false, 'error' => 'LinkedIn image upload failed.'];
                }
            }

            $body = [
                'author'          => $author,
                'lifecycleState'  => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary'    => ['text' => $text],
                        'shareMediaCategory' => $assetUrn ? 'IMAGE' : 'NONE',
                    ],
                ],
                'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
            ];
            if ($assetUrn) {
                $body['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [[
                    'status'      => 'READY',
                    'media'       => $assetUrn,
                ]];
            }

            $res = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->timeout(60)
                ->post(self::API . '/ugcPosts', $body);

            if (! $res->successful()) {
                return ['success' => false, 'error' => 'LinkedIn publish: ' . $res->body()];
            }

            $id  = $res->header('x-restli-id') ?: $res->json('id');
            $url = $id ? 'https://www.linkedin.com/feed/update/' . $id : null;

            return ['success' => true, 'external_post_id' => $id, 'external_url' => $url];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function uploadImage(PostAttempt $winner, string $author, string $token): ?string
    {
        $abs = Storage::disk('public')->path((string) $winner->file_path);
        if (! is_file($abs)) {
            return null;
        }

        $register = Http::withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->timeout(30)
            ->post(self::API . '/assets?action=registerUpload', [
                'registerUploadRequest' => [
                    'recipes'    => ['urn:li:digitalmediaRecipe:feedshare-image'],
                    'owner'      => $author,
                    'serviceRelationships' => [[
                        'relationshipType' => 'OWNER',
                        'identifier'       => 'urn:li:userGeneratedContent',
                    ]],
                ],
            ]);

        if (! $register->successful()) {
            Log::warning('LinkedIn registerUpload failed', ['body' => $register->body()]);
            return null;
        }

        // Keys literally contain dots, so read the decoded array directly
        // (Laravel's dot-notation would mis-parse them).
        $json      = $register->json();
        $mech      = $json['value']['uploadMechanism'] ?? [];
        $uploadUrl = $mech['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? null;
        $asset     = $json['value']['asset'] ?? null;
        if (! $uploadUrl || ! $asset) {
            return null;
        }

        $put = Http::withToken($token)
            ->withBody(file_get_contents($abs), $winner->mime ?: 'application/octet-stream')
            ->timeout(120)
            ->put($uploadUrl);

        return $put->successful() ? $asset : null;
    }
}
