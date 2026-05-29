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

    'sso' => [
        'redirect_uri' => env('SSO_REDIRECT_URI'),
        'easyfleet' => [
            'id' => env('SSO_EASYFLEET_ID'),
            'secret' => env('SSO_EASYFLEET_SECRET'),
            'authorization' => env('SSO_EASYFLEET_AUTHORIZATION'),
            'token' => env('SSO_EASYFLEET_TOKEN'),
            'userinfo' => env('SSO_EASYFLEET_USERINFO'),
            'jwks' => env('SSO_EASYFLEET_JWKS'),
            'url' => env('SSO_EASYFLEET_URL'),
        ],

            'microsoft' => [
            'id' => env('SSO_MICROSOFT_ID'),
            'secret' => env('SSO_MICROSOFT_SECRET'),
            'authorization' => env('SSO_MICROSOFT_AUTHORIZATION'),
            'token' => env('SSO_MICROSOFT_TOKEN'),
            'userinfo' => env('SSO_MICROSOFT_USERINFO'),
            'jwks' => env('SSO_MICROSOFT_JWKS'),
            'url' => env('SSO_MICROSOFT_URL'),
        ],
    ],

];
