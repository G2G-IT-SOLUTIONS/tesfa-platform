<?php

return [
    
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'telebirr' => [
        'app_id' => env('TELEBIRR_APP_ID'),
        'app_key' => env('TELEBIRR_APP_KEY'),
        'public_key' => env('TELEBIRR_PUBLIC_KEY'),
        'private_key' => env('TELEBIRR_PRIVATE_KEY'),
        'base_url' => env('TELEBIRR_BASE_URL'),
    ],

    'cbe' => [
        'api_key' => env('CBE_API_KEY'),
        'api_secret' => env('CBE_API_SECRET'),
        'base_url' => env('CBE_BASE_URL'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
    ],

    'exchange_rate' => [
        'api_key' => env('EXCHANGE_RATE_API_KEY'),
        'base_url' => 'https://v6.exchangerate-api.com/v6',
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'pagerduty' => [
        'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
    ],

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],
];