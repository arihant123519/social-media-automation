<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
    ],

    'gemini' => [
        'key'   => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL_ID', env('GEMINI_MODEL', 'gemini-2.5-flash')),
    ],

    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
    ],

    'instagram' => [
        'token'       => env('IG_ACCESS_TOKEN'),
        'business_id' => env('IG_BUSINESS_ID'),
    ],

    // Video transcoding (fixes IG Reels upload errors like 2207026 / 2207076).
    // Reels require: H.264 + AAC, ~30fps, 9:16 (1080x1920), yuv420p, +faststart.
    // If `bin` is null we auto-detect ffmpeg/ffprobe on PATH and common install dirs.
    'ffmpeg' => [
        'bin'     => env('FFMPEG_PATH'),     // e.g. C:\ffmpeg\bin\ffmpeg.exe
        'probe'   => env('FFPROBE_PATH'),    // e.g. C:\ffmpeg\bin\ffprobe.exe
        'enabled' => (bool) env('FFMPEG_NORMALIZE', true),
    ],

    'facebook' => [
        'page_id'    => env('FB_PAGE_ID'),
        'page_token' => env('FB_PAGE_ACCESS_TOKEN'),
    ],

    'linkedin' => [
        'client_id'     => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'access_token'  => env('LINKEDIN_ACCESS_TOKEN'),
        'author_urn'    => env('LINKEDIN_AUTHOR_URN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth providers (per-client Connect flow)
    |--------------------------------------------------------------------------
    | Used by OAuthController to onboard each client's social account.
    | Redirect URI must match exactly what's registered in the provider console.
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'meta' => [
        'app_id'     => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        // Facebook Login for Business — configuration id
        'config_id'  => env('META_CONFIG_ID'),
        'version'    => env('META_GRAPH_VERSION', 'v19.0'),
    ],

    // Fixed recipient for publish/reminder emails (overrides per-user email)
    'notify' => [
        'email' => env('NOTIFY_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

];
