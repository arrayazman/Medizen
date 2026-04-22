<?php

return [
    'url' => env('PACS_URL', 'http://localhost:8042'),
    'public_url' => env('PACS_PUBLIC_URL', env('PACS_URL', 'http://localhost:8042')),
    'username' => env('PACS_USERNAME', 'PACS'),
    'password' => env('PACS_PASSWORD', 'PACS'),
    'timeout' => env('PACS_TIMEOUT', 30),
];

