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

    'firestore' => [
        'enabled' => env('FIRESTORE_SYNC_ENABLED', false),
        'project_id' => env('FIRESTORE_PROJECT_ID'),
        'database' => env('FIRESTORE_DATABASE', '(default)'),
        'credentials_path' => env('FIRESTORE_CREDENTIALS_PATH', collect(glob(base_path('*firebase-adminsdk*.json')) ?: [])->first()),
        'timeout' => env('FIRESTORE_TIMEOUT', 5),
    ],

];
