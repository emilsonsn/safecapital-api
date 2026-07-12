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

    'btg' => [
        'environment' => env('BTG_ENVIRONMENT', 'SANDBOX'),
        'client_id' => env('BTG_CLIENT_ID'),
        'client_secret' => env('BTG_CLIENT_SECRET'),
        'authorize_url' => env('BTG_AUTHORIZE_URL', 'https://id.sandbox.btgpactual.com/oauth2/authorize'),
        'token_url' => env('BTG_TOKEN_URL'),
        'redirect_uri' => env('BTG_REDIRECT_URI'),
        'frontend_callback_url' => env('BTG_FRONTEND_CALLBACK_URL', env('APP_URL')),
        'api_url' => env('BTG_API_URL'),
        'company_id' => env('BTG_COMPANY_ID'),
        'account_id' => env('BTG_ACCOUNT_ID'),
        'account_branch' => env('BTG_ACCOUNT_BRANCH'),
        'account_number' => env('BTG_ACCOUNT_NUMBER'),
        'agreement' => env('BTG_AGREEMENT'),
        'scopes' => array_values(array_filter(explode(',', env(
            'BTG_SCOPES',
            'openid,brn:btg:empresas:banking:collections'
        )))),
    ],

];
