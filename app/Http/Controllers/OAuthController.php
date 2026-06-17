<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Per-client OAuth onboarding for YouTube + Instagram.
 *
 * Flow:
 *   /oauth/<platform>/connect/{client}    →  redirect to provider
 *   /oauth/<platform>/callback            →  exchange code, store tokens, redirect back to edit page
 *   /oauth/<platform>/disconnect/{client} →  clear tokens
 *
 * State param carries an encrypted blob: { client_id, csrf } to prevent CSRF + identify the
 * client across the redirect roundtrip.
 */
class OAuthController extends Controller
{
    private const YT_AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const YT_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const YT_SCOPES    = 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly';

    private const FB_AUTH_URL  = 'https://www.facebook.com/v21.0/dialog/oauth';
    private const FB_TOKEN_URL = 'https://graph.facebook.com/v21.0/oauth/access_token';
    // `instagram_manage_insights` is REQUIRED to read per-post reach/saved/shares
    // (the Growth-Intelligence tools). Without it the media list works but every
    // /{media}/insights call 400s, so metrics show "Not tracked yet".
    private const FB_SCOPES    = 'instagram_basic,instagram_content_publish,instagram_manage_insights,pages_show_list,pages_read_engagement';
    // Facebook Page publishing (separate "Connect Facebook" flow)
    private const FB_PAGE_SCOPES = 'pages_show_list,pages_manage_posts,pages_read_engagement';

    // ═══════════════════════════════════════════════════════════════
    //  YOUTUBE
    // ═══════════════════════════════════════════════════════════════

    public function youtubeConnect(Client $client)
    {
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') { 
            return back()->with('error', 'GOOGLE_CLIENT_ID not set in .env');
        }

        $state = $this->buildState($client->id);

        $params = http_build_query([
            'client_id'              => $clientId,
            'redirect_uri'           => route('oauth.youtube.callback'),
            'response_type'          => 'code',
            'scope'                  => self::YT_SCOPES,
            'access_type'            => 'offline',  // returns refresh_token
            'prompt'                 => 'consent',  // forces refresh_token even if previously granted
            'include_granted_scopes' => 'true',
            'state'                  => $state,
        ]);

        return redirect()->away(self::YT_AUTH_URL . '?' . $params);
    }

    public function youtubeCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('clients.index')->with('error', 'Google denied: ' . $request->get('error'));
        }

        $client = $this->resolveStateClient($request->get('state'));
        if (! $client) return redirect()->route('clients.index')->with('error', 'Invalid OAuth state.');

        $code = (string) $request->get('code');
        if ($code === '') return redirect()->route('clients.settings', $client)->with('error', 'No auth code returned.');

        try {
            $token = Http::asForm()->timeout(20)->post(self::YT_TOKEN_URL, [
                'code'          => $code,
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri'  => route('oauth.youtube.callback'),
                'grant_type'    => 'authorization_code',
            ]);

            if (! $token->ok()) {
                Log::warning('YT OAuth: token exchange failed', ['body' => $token->body()]);
                return redirect()->route('clients.settings', $client)
                    ->with('error', 'Token exchange failed: ' . ($token->json('error_description') ?? $token->body()));
            }

            $accessToken  = (string) $token->json('access_token');
            $refreshToken = (string) $token->json('refresh_token');
            $expiresIn    = (int)    $token->json('expires_in', 3600);

            if ($refreshToken === '') {
                // Already granted before with offline scope — fallback: keep existing refresh if any
                $refreshToken = $client->yt_refresh_token ?: '';
                if ($refreshToken === '') {
                    return redirect()->route('clients.settings', $client)
                        ->with('error', 'No refresh_token returned. Revoke app at https://myaccount.google.com/permissions and reconnect.');
                }
            }

            // Fetch the channel info so we can store the channel ID (display + verification)
            $channel = $this->fetchYoutubeChannel($accessToken);

            $client->update([
                'yt_refresh_token'     => $refreshToken,
                'yt_access_token'      => $accessToken,
                'yt_token_expires_at'  => now()->addSeconds($expiresIn - 60),
                'yt_channel_id'        => $channel['id'] ?? null,
            ]);

            return redirect()->route('clients.settings', $client)
                ->with('success', "YouTube connected" . (! empty($channel['title']) ? " — channel: {$channel['title']}" : ''));
        } catch (\Throwable $e) {
            Log::error('YT OAuth callback exception', ['e' => $e->getMessage()]);
            return redirect()->route('clients.settings', $client)->with('error', 'OAuth error: ' . $e->getMessage());
        }
    }

    public function youtubeDisconnect(Client $client)
    {
        $client->update([
            'yt_refresh_token'    => null,
            'yt_access_token'     => null,
            'yt_token_expires_at' => null,
            'yt_channel_id'       => null,
        ]);
        return redirect()->route('clients.settings', $client)->with('success', 'YouTube disconnected.');
    }

    // ═══════════════════════════════════════════════════════════════
    //  INSTAGRAM (via Meta / Facebook Graph)
    // ═══════════════════════════════════════════════════════════════

    public function instagramConnect(Client $client)
    {
        $appId = (string) config('services.meta.app_id');
        if ($appId === '') {
            return back()->with('error', 'META_APP_ID not set in .env');
        }

        $state = $this->buildState($client->id);

        $params = http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => route('oauth.instagram.callback'),
            'response_type' => 'code',
            'scope'         => self::FB_SCOPES,
            'state'         => $state,
            // Force fresh consent dialog every time (otherwise FB silently reuses
            // a prior approval, which skips the Page-selection step).
            'auth_type'     => 'rerequest',
        ]);

        return redirect()->away(self::FB_AUTH_URL . '?' . $params);
    }

    public function instagramCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('clients.index')->with('error', 'Meta denied: ' . $request->get('error_description', $request->get('error')));
        }

        $client = $this->resolveStateClient($request->get('state'));
        if (! $client) return redirect()->route('clients.index')->with('error', 'Invalid OAuth state.');

        $code = (string) $request->get('code');
        if ($code === '') return redirect()->route('clients.settings', $client)->with('error', 'No auth code returned.');

        try {
            // Step 1: code → short-lived token
            $short = Http::timeout(20)->get(self::FB_TOKEN_URL, [
                'client_id'     => config('services.meta.app_id'),
                'client_secret' => config('services.meta.app_secret'),
                'redirect_uri'  => route('oauth.instagram.callback'),
                'code'          => $code,
            ]);
            if (! $short->ok()) {
                return redirect()->route('clients.settings', $client)
                    ->with('error', 'Meta token exchange failed: ' . ($short->json('error.message') ?? $short->body()));
            }
            $shortToken = (string) $short->json('access_token');

            // Step 2: short-lived → long-lived (60 days)
            $long = Http::timeout(20)->get(self::FB_TOKEN_URL, [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => config('services.meta.app_id'),
                'client_secret'     => config('services.meta.app_secret'),
                'fb_exchange_token' => $shortToken,
            ]);
            $longToken = (string) $long->json('access_token', $shortToken);

            // Step 3: fetch pages → collect every page that has an IG business account
            $pages = Http::timeout(20)->get('https://graph.facebook.com/v21.0/me/accounts', [
                'access_token' => $longToken,
                'fields'       => 'id,name,access_token,instagram_business_account{id,username}',
            ]);

            // ── TEMP DEBUG (remove after diagnosing IG connect) ──
            $perms = Http::timeout(20)->get('https://graph.facebook.com/v21.0/me/permissions', [
                'access_token' => $longToken,
            ]);
            $me = Http::timeout(20)->get('https://graph.facebook.com/v21.0/me', [
                'access_token' => $longToken,
                'fields'       => 'id,name',
            ]);
            Log::info('IG OAuth DEBUG', [
                'fb_user'        => $me->json(),
                'pages_status'   => $pages->status(),
                'pages_body'     => $pages->json(),
                'granted_perms'  => $perms->json('data'),
            ]);
            // ── END TEMP DEBUG ──

            $pagesData  = $pages->json('data', []);
            $candidates = [];
            foreach ($pagesData as $p) {
                if (! empty($p['instagram_business_account']['id'])) {
                    $candidates[] = [
                        'page_id'     => $p['id'] ?? null,
                        'page_name'   => $p['name'] ?? '(unnamed page)',
                        'ig_id'       => $p['instagram_business_account']['id'],
                        'ig_username' => $p['instagram_business_account']['username'] ?? null,
                        'page_token'  => $p['access_token'] ?? $longToken,
                    ];
                }
            }

            if (empty($candidates)) {
                return redirect()->route('clients.settings', $client)
                    ->with('error', 'No Instagram Business account found on any linked Facebook Page. Convert your IG to Business + link to a Page, then try again.');
            }

            // Single account → connect directly. Multiple → let the user choose (#5).
            if (count($candidates) === 1) {
                return $this->saveInstagramSelection($client, $candidates[0]);
            }

            session(['ig_oauth_candidates' => ['client_id' => $client->id, 'pages' => $candidates]]);
            return view('oauth.select-instagram', ['client' => $client, 'pages' => $candidates]);
        } catch (\Throwable $e) {
            Log::error('IG OAuth callback exception', ['e' => $e->getMessage()]);
            return redirect()->route('clients.settings', $client)->with('error', 'OAuth error: ' . $e->getMessage());
        }
    }

    public function instagramDisconnect(Client $client)
    {
        $client->update([
            'ig_access_token' => null,
            'ig_business_id'  => null,
        ]);
        return redirect()->route('clients.settings', $client)->with('success', 'Instagram disconnected.');
    }

    /**
     * #5 — User picked one IG account from the multi-page selection screen.
     */
    public function instagramSelectPage(Request $request)
    {
        $data   = $request->validate(['ig_id' => 'required|string']);
        $stored = session('ig_oauth_candidates');

        if (! $stored || empty($stored['client_id'])) {
            return redirect()->route('clients.index')->with('error', 'Selection expired — please connect Instagram again.');
        }

        $client = Client::find($stored['client_id']);
        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        $selected = collect($stored['pages'])->firstWhere('ig_id', $data['ig_id']);
        if (! $selected) {
            return redirect()->route('clients.settings', $client)->with('error', 'Invalid Instagram account selection.');
        }

        session()->forget('ig_oauth_candidates');
        return $this->saveInstagramSelection($client, $selected);
    }

    /**
     * Persist the chosen page-scoped token + IG business id onto the client.
     *
     * @param  array{ig_id: string, ig_username: ?string, page_token: string}  $selected
     */
    private function saveInstagramSelection(Client $client, array $selected)
    {
        $client->update([
            'ig_access_token' => $selected['page_token'],  // page-scoped, long-lived
            'ig_business_id'  => $selected['ig_id'],
        ]);

        $label = $selected['ig_username'] ? '@' . $selected['ig_username'] : "IG ID: {$selected['ig_id']}";
        return redirect()->route('clients.settings', $client)
            ->with('success', "Instagram connected — {$label}");
    }

    // ═══════════════════════════════════════════════════════════════
    //  FACEBOOK PAGE (publish to a Facebook Page)
    // ═══════════════════════════════════════════════════════════════

    public function facebookConnect(Client $client)
    {
        $appId = (string) config('services.meta.app_id');
        if ($appId === '') {
            return back()->with('error', 'META_APP_ID not set in .env');
        }

        $params = http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => route('oauth.facebook.callback'),
            'response_type' => 'code',
            'scope'         => self::FB_PAGE_SCOPES,
            'state'         => $this->buildState($client->id),
            'auth_type'     => 'rerequest',
        ]);

        return redirect()->away(self::FB_AUTH_URL . '?' . $params);
    }

    public function facebookCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('clients.index')->with('error', 'Meta denied: ' . $request->get('error_description', $request->get('error')));
        }

        $client = $this->resolveStateClient($request->get('state'));
        if (! $client) return redirect()->route('clients.index')->with('error', 'Invalid OAuth state.');

        $code = (string) $request->get('code');
        if ($code === '') return redirect()->route('clients.settings', $client)->with('error', 'No auth code returned.');

        try {
            // code → short-lived → long-lived user token
            $short = Http::timeout(20)->get(self::FB_TOKEN_URL, [
                'client_id'     => config('services.meta.app_id'),
                'client_secret' => config('services.meta.app_secret'),
                'redirect_uri'  => route('oauth.facebook.callback'),
                'code'          => $code,
            ]);
            if (! $short->ok()) {
                return redirect()->route('clients.settings', $client)
                    ->with('error', 'Meta token exchange failed: ' . ($short->json('error.message') ?? $short->body()));
            }

            $long = Http::timeout(20)->get(self::FB_TOKEN_URL, [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => config('services.meta.app_id'),
                'client_secret'     => config('services.meta.app_secret'),
                'fb_exchange_token' => (string) $short->json('access_token'),
            ]);
            $longToken = (string) $long->json('access_token', (string) $short->json('access_token'));

            // Pages → each carries a page-scoped (never-expiring) access_token
            $pages = Http::timeout(20)->get('https://graph.facebook.com/v21.0/me/accounts', [
                'access_token' => $longToken,
                'fields'       => 'id,name,access_token',
            ]);

            $candidates = [];
            foreach ($pages->json('data', []) as $p) {
                if (! empty($p['id']) && ! empty($p['access_token'])) {
                    $candidates[] = [
                        'page_id'    => $p['id'],
                        'page_name'  => $p['name'] ?? '(unnamed page)',
                        'page_token' => $p['access_token'],
                    ];
                }
            }

            if (empty($candidates)) {
                return redirect()->route('clients.settings', $client)
                    ->with('error', 'No Facebook Pages found for this account. Create/assign a Page, then try again.');
            }

            if (count($candidates) === 1) {
                return $this->saveFacebookSelection($client, $candidates[0]);
            }

            session(['fb_oauth_candidates' => ['client_id' => $client->id, 'pages' => $candidates]]);
            return view('oauth.select-facebook', ['client' => $client, 'pages' => $candidates]);
        } catch (\Throwable $e) {
            Log::error('FB OAuth callback exception', ['e' => $e->getMessage()]);
            return redirect()->route('clients.settings', $client)->with('error', 'OAuth error: ' . $e->getMessage());
        }
    }

    public function facebookSelectPage(Request $request)
    {
        $data   = $request->validate(['page_id' => 'required|string']);
        $stored = session('fb_oauth_candidates');

        if (! $stored || empty($stored['client_id'])) {
            return redirect()->route('clients.index')->with('error', 'Selection expired — please connect Facebook again.');
        }

        $client = Client::find($stored['client_id']);
        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        $selected = collect($stored['pages'])->firstWhere('page_id', $data['page_id']);
        if (! $selected) {
            return redirect()->route('clients.settings', $client)->with('error', 'Invalid Page selection.');
        }

        session()->forget('fb_oauth_candidates');
        return $this->saveFacebookSelection($client, $selected);
    }

    private function saveFacebookSelection(Client $client, array $selected)
    {
        $client->update([
            'fb_page_id'    => $selected['page_id'],
            'fb_page_token' => $selected['page_token'],  // page-scoped, never-expiring
        ]);

        return redirect()->route('clients.settings', $client)
            ->with('success', "Facebook connected — Page: {$selected['page_name']}");
    }

    public function facebookDisconnect(Client $client)
    {
        $client->update(['fb_page_id' => null, 'fb_page_token' => null]);
        return redirect()->route('clients.settings', $client)->with('success', 'Facebook disconnected.');
    }

    // ═══════════════════════════════════════════════════════════════
    //  FACEBOOK LOGIN (JS SDK) — one popup connects FB Page + Instagram
    //  The browser logs in via the FB SDK and sends us the SHORT-lived
    //  token. We exchange it to LONG-lived server-side (App Secret never
    //  leaves the server), then read the user's Pages + linked IG accounts.
    // ═══════════════════════════════════════════════════════════════

    public function metaConnect(Request $request, Client $client)
    {
        $request->validate(['access_token' => 'required|string']);

        $appId  = (string) config('services.meta.app_id');
        $secret = (string) config('services.meta.app_secret');
        if ($appId === '' || $secret === '') {
            return response()->json(['success' => false, 'error' => 'Meta App ID/Secret not set. Save them first.'], 422);
        }

        // 1) short-lived → long-lived (60-day) user token, server-side
        $ll        = app(\App\Services\MetaTokenService::class)->longLived((string) $request->input('access_token'), $appId, $secret);
        $userToken = $ll['token'] ?? (string) $request->input('access_token');

        // 2) read Pages + linked IG business accounts (each Page carries its own token)
        $version = (string) config('services.meta.version', 'v19.0');
        $res = Http::timeout(20)->get("https://graph.facebook.com/{$version}/me/accounts", [
            'access_token' => $userToken,
            'fields'       => 'id,name,access_token,instagram_business_account{id,username}',
        ]);

        if (! $res->ok()) {
            return response()->json(['success' => false, 'error' => 'Graph error: ' . ($res->json('error.message') ?? $res->body())], 422);
        }

        $candidates = [];
        foreach ($res->json('data', []) as $p) {
            if (empty($p['id']) || empty($p['access_token'])) continue;
            $candidates[] = [
                'page_id'     => $p['id'],
                'page_name'   => $p['name'] ?? '(unnamed page)',
                'page_token'  => $p['access_token'],
                'ig_id'       => $p['instagram_business_account']['id'] ?? null,
                'ig_username' => $p['instagram_business_account']['username'] ?? null,
            ];
        }

        if (empty($candidates)) {
            // Diagnose WHY: which permissions did the login actually grant?
            $perms = Http::timeout(15)->get("https://graph.facebook.com/{$version}/me/permissions", [
                'access_token' => $userToken,
            ]);
            $granted = collect($perms->json('data', []))
                ->where('status', 'granted')->pluck('permission')->all();

            Log::warning('metaConnect: no pages returned', [
                'client_id'      => $client->id,
                'accounts_body'  => $res->json(),
                'granted_perms'  => $granted,
            ]);

            $needed  = ['pages_show_list', 'pages_manage_posts', 'business_management'];
            $missing = array_values(array_diff($needed, $granted));

            $hint = $missing
                ? 'Login did not grant: ' . implode(', ', $missing) . '. Fix your Facebook Login Configuration (config_id) to request these permissions, and during login pick the Business + Page(s) to share.'
                : 'Permissions are granted but no Page was selected/exists. In the login popup choose "Opt in to all" and select your Page; or create a Facebook Page and link your IG Business account to it.';

            return response()->json([
                'success' => false,
                'error'   => 'No Facebook Page accessible. ' . $hint,
                'granted' => $granted,
            ], 422);
        }

        // Single page → connect immediately. Multiple → return list for the user to pick.
        if (count($candidates) === 1) {
            return response()->json($this->persistMetaSelection($client, $candidates[0]));
        }

        session(['meta_login_candidates' => ['client_id' => $client->id, 'pages' => $candidates]]);

        return response()->json([
            'success'         => true,
            'needs_selection' => true,
            'pages'           => array_map(fn ($c) => [
                'page_id'     => $c['page_id'],
                'page_name'   => $c['page_name'],
                'ig_username' => $c['ig_username'],
            ], $candidates),
        ]);
    }

    public function metaSelect(Request $request, Client $client)
    {
        $data   = $request->validate(['page_id' => 'required|string']);
        $stored = session('meta_login_candidates');

        if (! $stored || (int) ($stored['client_id'] ?? 0) !== $client->id) {
            return response()->json(['success' => false, 'error' => 'Selection expired — log in again.'], 422);
        }

        $selected = collect($stored['pages'])->firstWhere('page_id', $data['page_id']);
        if (! $selected) {
            return response()->json(['success' => false, 'error' => 'Invalid Page selection.'], 422);
        }

        session()->forget('meta_login_candidates');
        return response()->json($this->persistMetaSelection($client, $selected));
    }

    /**
     * Persist the chosen Page's FB token + (if present) its IG business account
     * onto the client. One action connects both Facebook and Instagram.
     */
    private function persistMetaSelection(Client $client, array $sel): array
    {
        $update = [
            'fb_page_id'    => $sel['page_id'],
            'fb_page_token' => $sel['page_token'],
        ];
        if (! empty($sel['ig_id'])) {
            $update['ig_access_token'] = $sel['page_token']; // page-scoped token works for IG publishing
            $update['ig_business_id']  = $sel['ig_id'];
        }
        $client->update($update);

        return [
            'success'    => true,
            'facebook'   => $sel['page_name'],
            'instagram'  => $sel['ig_username'] ? '@' . $sel['ig_username'] : null,
            'message'    => 'Connected Facebook Page “' . $sel['page_name'] . '”'
                            . (! empty($sel['ig_id']) ? ' + Instagram ' . ($sel['ig_username'] ? '@' . $sel['ig_username'] : $sel['ig_id']) : ''),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build an encrypted state token that carries the client id + a random nonce.
     * The receiving side decrypts and verifies before acting on it.
     */
    private function buildState(int $clientId): string
    {
        return encrypt(['client_id' => $clientId, 'nonce' => Str::random(16), 'ts' => time()]);
    }

    private function resolveStateClient(?string $state): ?Client
    {
        if (! $state) return null;
        try {
            $payload = decrypt($state);
            $age = time() - (int) ($payload['ts'] ?? 0);
            if ($age > 600) return null; // 10-min expiry
            return Client::find($payload['client_id'] ?? null);
        } catch (\Throwable) {
            return null;
        }
    }

    private function fetchYoutubeChannel(string $accessToken): array
    {
        try {
            $res = Http::timeout(15)->withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet',
                    'mine' => 'true',
                ]);
            $item = $res->json('items.0', []);
            return [
                'id'    => $item['id'] ?? null,
                'title' => $item['snippet']['title'] ?? null,
            ];
        } catch (\Throwable) {
            return ['id' => null, 'title' => null];
        }
    }
}
