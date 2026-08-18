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

    'yo' => [
        'username' => env('YO_API_USERNAME'),
        'password' => env('YO_API_PASSWORD'),
        'mode' => env('YO_MODE', 'sandbox'),
        'sandbox_url' => env('YO_SANDBOX_URL', 'https://sandbox.yo.co.ug/services/yopaymentsdev/task.php'),
        'live_url' => env('YO_LIVE_URL', 'https://paymentsapi1.yo.co.ug/ybs/task.php'),
    ],

];
