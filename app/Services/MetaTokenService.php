<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta (Instagram + Facebook) token lifecycle helper.
 *
 * Solves "tokens expire every time": a token pasted from Graph API Explorer is
 * short-lived (~1-2h). This exchanges it for a LONG-LIVED token (~60 days), and
 * the meta:refresh-tokens cron re-exchanges it weekly so it never lapses.
 *
 * For Pages, a long-lived USER token can mint a never-expiring PAGE token.
 */
class MetaTokenService
{
    private const BASE = 'https://graph.facebook.com/v18.0';

    private function creds(?string $appId, ?string $appSecret): array
    {
        return [
            $appId     ?: (string) config('services.meta.app_id'),
            $appSecret ?: (string) config('services.meta.app_secret'),
        ];
    }

    /**
     * Exchange ANY user token (short- or long-lived) for a fresh long-lived one.
     * Re-running on a long-lived token extends its 60-day window — that's how
     * the weekly cron keeps it alive indefinitely.
     *
     * @return array{token: string, expires_in: int}|null
     */
    public function longLived(string $token, ?string $appId = null, ?string $appSecret = null): ?array
    {
        [$appId, $appSecret] = $this->creds($appId, $appSecret);
        if ($appId === '' || $appSecret === '' || $token === '') {
            return null;
        }

        try {
            $res = Http::timeout(20)->get(self::BASE . '/oauth/access_token', [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $appId,
                'client_secret'     => $appSecret,
                'fb_exchange_token' => $token,
            ]);

            if (! $res->ok()) {
                Log::warning('Meta long-lived exchange failed', ['body' => $res->body()]);
                return null;
            }

            $newToken = (string) $res->json('access_token', '');
            if ($newToken === '') {
                return null;
            }

            // FB omits expires_in for never-expiring (page) tokens → treat as ~60d.
            $expiresIn = (int) $res->json('expires_in', 60 * 24 * 3600);

            return ['token' => $newToken, 'expires_in' => $expiresIn];
        } catch (\Throwable $e) {
            Log::warning('Meta long-lived exchange exception', ['e' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Mint a (never-expiring) Page access token from a long-lived user token.
     */
    public function pageToken(string $userToken, string $pageId): ?string
    {
        try {
            $res = Http::timeout(20)->get(self::BASE . "/{$pageId}", [
                'fields'       => 'access_token',
                'access_token' => $userToken,
            ]);
            return $res->ok() ? $res->json('access_token') : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
