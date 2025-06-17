<?php

return [
    'auth' => [
        'jwt_public_key' => env('JWT_PUBLIC_KEY_PATH'), // o URL
        'jwt_algorithm' => 'RS256',
        'header' => 'Authorization', // o 'X-Access-Token'
    ],

    'api_gateway' => [
        'url' => env('API_GATEWAY_URL', 'http://gateway.local'),
    ],
];
