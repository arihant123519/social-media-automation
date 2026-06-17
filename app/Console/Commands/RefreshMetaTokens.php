<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\MetaTokenService;
use Illuminate\Console\Command;

/**
 * Keeps Meta (Instagram + Facebook) long-lived tokens alive forever.
 *
 * Re-exchanging a long-lived token before it expires returns a fresh 60-day
 * token. Running this weekly means the stored tokens never actually lapse, so
 * the team never has to re-login / re-paste. Runs via the scheduler.
 */
class RefreshMetaTokens extends Command
{
    protected $signature = 'meta:refresh-tokens';
    protected $description = 'Extend stored Instagram/Facebook long-lived tokens so they never expire';

    public function handle(MetaTokenService $meta): int
    {
        $values    = Setting::map();
        $appId     = $values['meta_app_id']     ?? config('services.meta.app_id');
        $appSecret = $values['meta_app_secret'] ?? config('services.meta.app_secret');

        if (! $appId || ! $appSecret) {
            $this->warn('Meta App ID/Secret not set — cannot refresh tokens.');
            return self::SUCCESS;
        }

        $targets = [
            'ig_access_token' => ['exp' => 'ig_token_expires_at', 'group' => 'instagram', 'label' => 'Instagram'],
            'fb_page_token'   => ['exp' => 'fb_token_expires_at', 'group' => 'facebook',  'label' => 'Facebook'],
        ];

        $refreshed = 0;
        foreach ($targets as $tokenKey => $meta2) {
            $current = $values[$tokenKey] ?? null;
            if (! $current) {
                continue;
            }

            $ll = $meta->longLived((string) $current, $appId, $appSecret);
            if (! $ll) {
                $this->error("  ✗ {$meta2['label']} refresh failed (token may be invalid — reconnect needed).");
                continue;
            }

            Setting::put($tokenKey, $ll['token'], true, $meta2['group']);
            Setting::put($meta2['exp'], now()->addSeconds($ll['expires_in'])->toDateTimeString(), false, $meta2['group']);
            $this->info("  ✓ {$meta2['label']} token extended (valid ~" . round($ll['expires_in'] / 86400) . " more days).");
            $refreshed++;
        }

        $this->info("Done. Refreshed {$refreshed} token(s).");
        return self::SUCCESS;
    }
}
