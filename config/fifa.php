<?php

use App\Services\AuthClient;
use App\Services\DavinciClient;
use App\Services\ResaleClient;

return [
    'auth' => [
        'username' => env('FIFA_USERNAME'),
        'password' => env('FIFA_PASSWORD'),
    ],

    'clients' => [
        'resale' => [
            'class' => ResaleClient::class,
            'http'  => [
                'base_uri' => 'https://fwc26-resale-usd.tickets.fifa.com/secure/'
            ]
        ],

        'davinci' => [
            'class' => DavinciClient::class,
            'http'  => [
                'base_uri' => 'https://auth.fifa.com/davinci/'
            ],
        ],

        'auth' => [
            'class' => AuthClient::class,
            'http'  => [
                'base_uri' => 'https://auth.fifa.com/'
            ],
        ]
    ]
];
