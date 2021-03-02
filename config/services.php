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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'zoho' => [
        'client_id' => env('ZOHO_CLIENT_ID', '1000.9XAKQT44G98AX52QDO12V8A4ZLWJ6X'),
        'client_secret' => env('ZOHO_CLIENT_SECRET', 'a28dda16012b5c8b989fea78f1e281b9ad77cba830'),
        'user_email_id' => env('ZOHO_USER_EMAIL_ID', 'developer@elementsecurity.com.au'),
        'api_domain' => env('ZOHO_DOMAIN', "https://accounts.zoho.com.au")
    ],

    'simpro' => [
        'api_key' => env('SIM_API_KEY', '3b7197a18bb4b2044c62bd119db6711f2df299ef'),
        'client_url' => env('SIM_URL', 'https://element.simprosuite.com/'),
        'webhook_secret' => env('SIM_WEBHOOK_SECRET')
    ]

];
