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

    'firebase' => [
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://pacs-7c02d-default-rtdb.asia-southeast1.firebasedatabase.app/'),
        'activation_url' => env('FIREBASE_ACTIVATION_URL'),
        'verify_url' => env('FIREBASE_VERIFY_URL'),
    ],

    'orthanc' => [
        'url' => env('PACS_URL', 'http://localhost:8042'),
        'username' => env('PACS_USERNAME', 'orthanc'),
        'password' => env('PACS_PASSWORD', 'orthanc'),
    ],

];
