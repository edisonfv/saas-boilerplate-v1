<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Electronic-signature provider (App\Services\Signatures\Uanataca).
    // API v4 authenticates with a Bearer token; the legacy apikey/uid pair
    // is sent in the body only when no token is configured.
    'uanataca' => [
        'base_url' => env('UANATACA_BASE_URL', 'https://api.uanataca.ec'),
        'token' => env('UANATACA_TOKEN'),
        'api_key' => env('UANATACA_API_KEY'),
        'uid' => env('UANATACA_UID'),
        'timeout' => (int) env('UANATACA_TIMEOUT', 90),
        // Bearer token Uanataca must send when calling our webhook.
        'webhook_token' => env('UANATACA_WEBHOOK_TOKEN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
