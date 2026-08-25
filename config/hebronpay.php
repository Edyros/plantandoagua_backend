<?php

return [
    /*
    | HebronPay (white-label Gatewee).
    | Documentação: https://app.hebronpay.com.br/documentation
    | As chaves nunca devem ir para o app. Só o backend fala com a HebronPay.
    */
    'base_url' => rtrim((string) env('HEBRONPAY_BASE_URL', 'https://api.hebronpay.com.br/v1'), '/'),
    'api_key' => env('HEBRONPAY_API_KEY'),
    'public_key' => env('HEBRONPAY_PUBLIC_KEY'),
    'api_secret' => env('HEBRONPAY_API_SECRET'),
    'webhook_secret' => env('HEBRONPAY_WEBHOOK_SECRET'),
    'webhook_url' => env('HEBRONPAY_WEBHOOK_URL'),
    'timeout' => (int) env('HEBRONPAY_TIMEOUT', 30),
];
