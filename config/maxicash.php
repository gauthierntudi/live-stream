<?php

$apiMode = env('MAXICASH_API_MODE', 'test');

return [
    'merchant_id' => env('MAXICASH_MERCHANT_ID'),
    'merchant_password' => env('MAXICASH_MERCHANT_PASSWORD'),
    /**
     * test | live — les URLs par défaut pointent vers webapi-test (doc MaxiCash) ou webapi (prod).
     */
    'api_mode' => $apiMode,
    /**
     * Optionnel : si défini, les POST webhook doivent envoyer Authorization: Bearer {secret}.
     */
    'webhook_secret' => env('MAXICASH_WEBHOOK_SECRET'),
    'mobile_url' => env(
        'MAXICASH_MOBILE_URL',
        $apiMode === 'live'
            ? 'https://webapi.maxicashapp.com/Integration/PayNowSync'
            : 'https://webapi-test.maxicashapp.com/Integration/PayNowSync'
    ),
    /*
     * Carte — étape 1 : PayEntryWeb (PAS PayCreditCard).
     * MAXICASH_CARD_URL est conservé pour compatibilité mais ignoré s’il pointe vers PayCreditCard.
     */
    'pay_entry_web_url' => env(
        'MAXICASH_PAY_ENTRY_WEB_URL',
        $apiMode === 'live'
            ? 'https://webapi.maxicashapp.com/Integration/PayEntryWeb'
            : 'https://webapi-test.maxicashapp.com/Integration/PayEntryWeb'
    ),
    /** @deprecated Utiliser pay_entry_web_url — ne pas définir sur PayCreditCard pour le flux redirection */
    'card_url' => env('MAXICASH_CARD_URL'),
    'gateway_checkout_url' => env(
        'MAXICASH_GATEWAY_CHECKOUT_URL',
        $apiMode === 'live'
            ? 'https://api.maxicashapp.com/payentryweb'
            : 'https://api-testbed.maxicashapp.com/payentryweb'
    ),
    /**
     * PayNowSync : la doc MaxiCash indique que l’API peut attendre jusqu’à ~420 s pendant que
     * l’utilisateur valide sur le téléphone (USSD / notification). Un timeout trop court coupe
     * la requête avant la fin du flux et l’invite USSD peut ne pas aboutir correctement.
     *
     * En production : augmente aussi nginx (fastcgi_read_timeout / proxy_read_timeout) et PHP
     * (max_execution_time) au‑delà de cette valeur, sinon risque de 502.
     */
    'paynow_timeout' => max(30, (int) env('MAXICASH_PAYNOW_TIMEOUT', 420)),
    /**
     * PayCreditCard : réponse souvent rapide (URL 3‑DS en pending) ; timeout plus court acceptable.
     */
    'card_timeout' => max(15, (int) env('MAXICASH_CARD_TIMEOUT', 120)),
    'connect_timeout' => max(2, (int) env('MAXICASH_CONNECT_TIMEOUT', 30)),
    'http_attempts' => max(1, (int) env('MAXICASH_HTTP_ATTEMPTS', 1)),
];
