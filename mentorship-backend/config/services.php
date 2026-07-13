<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

    'mailtrap' => [
        'host' => env('MAILTRAP_HOST', 'send.api.mailtrap.io'),
        'secret' => env('MAILTRAP_SECRET'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/api/auth/google/callback',
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/api/auth/linkedin/callback',
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/api/auth/github/callback',
    ],

    'rapidapi' => [
        'key' => env('RAPIDAPI_KEY'),
        'host' => env('RAPIDAPI_HOST', 'jsearch.p.rapidapi.com'),
        'country' => env('RAPIDAPI_COUNTRY', 'my'),
        'num_pages' => env('RAPIDAPI_NUM_PAGES', 3),
        'date_posted' => env('RAPIDAPI_DATE_POSTED', 'week'),
        'allowed_sources' => array_filter(array_map('trim', explode(',', env('RAPIDAPI_ALLOWED_SOURCES', 'LinkedIn,JobStreet,MauKerja')))),
    ],

];
