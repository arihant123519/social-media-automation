<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'encrypted'];

    protected $casts = ['encrypted' => 'boolean'];

    public const CACHE_KEY = 'app_settings';

    /**
     * Single source of truth for every manageable setting.
     * setting key => [config path, group, encrypt-at-rest, label, secret?]
     * "secret" fields render as password inputs and are left untouched when
     * submitted blank (so a stored secret isn't wiped by an empty form field).
     */
    public static function definitions(): array
    {
        return [
            // AI
            'gemini_key'          => ['services.gemini.key',                 'ai',        true,  'Gemini API Key',        true],
            'gemini_model'        => ['services.gemini.model',               'ai',        false, 'Gemini Model',          false],
            // YouTube
            'youtube_key'         => ['services.youtube.key',                'youtube',   true,  'YouTube API Key',       true],
            // Instagram
            'ig_access_token'     => ['services.instagram.token',            'instagram', true,  'IG Access Token',       true],
            'ig_business_id'      => ['services.instagram.business_id',      'instagram', false, 'IG Business ID',        false],
            // Facebook (Pages)
            'fb_page_id'          => ['services.facebook.page_id',           'facebook',  false, 'FB Page ID',            false],
            'fb_page_token'       => ['services.facebook.page_token',        'facebook',  true,  'FB Page Access Token',  true],
            // Meta App
            'meta_app_id'         => ['services.meta.app_id',                'meta',      false, 'Meta App ID',           false],
            'meta_app_secret'     => ['services.meta.app_secret',            'meta',      true,  'Meta App Secret',       true],
            'meta_config_id'      => ['services.meta.config_id',             'meta',      false, 'Meta Login Config ID',  false],
            // Google OAuth
            'google_client_id'    => ['services.google.client_id',           'google',    false, 'Google Client ID',      false],
            'google_client_secret'=> ['services.google.client_secret',       'google',    true,  'Google Client Secret',  true],
            'yt_access_token'     => ['services.google.yt_access_token',     'google',    true,  'YouTube Access Token (fallback)', true],
            // Mail / SMTP
            'mail_mailer'         => ['mail.default',                        'mail',      false, 'Mailer',                false],
            'mail_host'           => ['mail.mailers.smtp.host',              'mail',      false, 'SMTP Host',             false],
            'mail_port'           => ['mail.mailers.smtp.port',              'mail',      false, 'SMTP Port',             false],
            'mail_username'       => ['mail.mailers.smtp.username',          'mail',      false, 'SMTP Username',         false],
            'mail_password'       => ['mail.mailers.smtp.password',          'mail',      true,  'SMTP Password',         true],
            'mail_encryption'     => ['mail.mailers.smtp.scheme',            'mail',      false, 'SMTP Encryption (tls/ssl)', false],
            'mail_from_address'   => ['mail.from.address',                   'mail',      false, 'From Address',          false],
            'mail_from_name'      => ['mail.from.name',                      'mail',      false, 'From Name',             false],
            // App
            'app_url'             => ['app.url',                             'app',       false, 'App URL',               false],
            'app_timezone'        => ['app.timezone',                        'app',       false, 'Timezone',              false],
            'notify_email'        => ['services.notify.email',               'app',       false, 'Notification Email',    false],
            'approval_score'      => ['publishing.approval_score',           'app',       false, 'Min Approval Score (0-100)', false],
        ];
    }

    /**
     * [key => decrypted value] for every stored setting. Cached forever;
     * busted on every put(). Decryption is guarded so a rotated APP_KEY or
     * corrupt row can never crash app boot — it just yields null for that key.
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::all()->mapWithKeys(function (Setting $s) {
                $value = $s->value;
                if ($s->encrypted && $value !== null && $value !== '') {
                    try {
                        $value = Crypt::decryptString($value);
                    } catch (\Throwable) {
                        $value = null;
                    }
                }
                return [$s->key => $value];
            })->all();
        });
    }

    public static function put(string $key, ?string $value, bool $encrypt = false, string $group = 'app'): void
    {
        $stored = ($encrypt && $value !== null && $value !== '')
            ? Crypt::encryptString($value)
            : $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'encrypted' => $encrypt, 'group' => $group],
        );

        static::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
