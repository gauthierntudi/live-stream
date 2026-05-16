<?php

return [
    /*
    | Fichiers attendus dans resources/img/icons/ (servis via /img/icons/…).
    */
    'paypal_me_url' => env('PAYPAL_ME_URL', 'https://www.paypal.me/AtmosphereFam'),

    'methods' => [
        [
            'id' => 'card',
            'label' => 'Carte bancaire',
            'icon' => 'card.jpg',
        ],
        [
            'id' => 'paypal',
            'label' => 'PayPal',
            'icon' => 'paypal.jpg',
            'external' => true,
        ],
        [
            'id' => 'mpesa',
            'label' => 'M-Pesa',
            'icon' => 'mpesa01.jpg',
        ],
        [
            'id' => 'airtel',
            'label' => 'Airtel Money',
            'icon' => 'airtel.jpg',
        ],
        [
            'id' => 'orange',
            'label' => 'Orange Money',
            'icon' => 'orange.jpg',
        ],
    ],
];
