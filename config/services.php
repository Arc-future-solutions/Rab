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

    'calendly' => [
        'api_token'      => env('CALENDLY_API_TOKEN', ''),
        'user_uri'       => env('CALENDLY_USER_URI', ''),
        'embed_url'      => env('CALENDLY_EMBED_URL', ''),
        'url'            => env('CALENDLY_URL', ''),
        'webhook_secret' => env('CALENDLY_WEBHOOK_SECRET', ''),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY', env('CLAUDE_API_KEY')),
        'api_key' => env('ANTHROPIC_API_KEY', env('CLAUDE_API_KEY')),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 8192),
        'timeout' => env('ANTHROPIC_TIMEOUT', 120),
    ],

    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
    ],

    'crm' => [
        'high_priority_email' => env('CRM_HIGH_PRIORITY_EMAIL', 'rboukhiar@rabconsultingservices.com'),
    ],

];
