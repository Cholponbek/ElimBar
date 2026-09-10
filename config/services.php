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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // PAYMENT_PROVIDER читается только здесь, не напрямую через env() в
    // AppServiceProvider — после config:cache (см. docker/php/entrypoint.sh)
    // .env больше не читается, только этот закэшированный файл.
    'payment_provider' => env('PAYMENT_PROVIDER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Finik (эквайринг, acquiring.averspay.kg)
    |--------------------------------------------------------------------------
    |
    | Ключи хранятся в .env как base64 от PEM-файла целиком (не сам PEM
    | построчно) — многострочные значения не переживают ручной ввод через
    | консоль хостера надёжно (см. историю правок APP_KEY на прод-серверах).
    |   base64 -w0 finik-private.pem
    |
    | webhook_public_key — это ключ САМОГО Finik, которым проверяется
    | подпись входящих вебхуков. Отдельный ключ от того, что мы им
    | отправили. На момент написания ещё не получен от Finik — пока он не
    | задан, verifyWebhookSignature() всегда возвращает false, и донаты
    | не подтверждаются по вебхуку (см. FinikPaymentGateway).
    |
    */
    'finik' => [
        'base_url' => rtrim(env('FINIK_BASE_URL', 'https://beta.api.acquiring.averspay.kg'), '/'),
        'account_id' => env('FINIK_ACCOUNT_ID'),
        'api_key' => env('FINIK_API_KEY'),
        'private_key' => env('FINIK_PRIVATE_KEY_BASE64') ? base64_decode(env('FINIK_PRIVATE_KEY_BASE64')) : null,
        'webhook_public_key' => env('FINIK_WEBHOOK_PUBLIC_KEY_BASE64') ? base64_decode(env('FINIK_WEBHOOK_PUBLIC_KEY_BASE64')) : null,
        'webhook_url' => env('FINIK_WEBHOOK_URL'),
        'merchant_name' => env('FINIK_MERCHANT_NAME', 'Элим, барсыңбы?!'),
    ],

];
