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

    /*
    | EIA (U.S. Energy Information Administration) — jet yakıtı spot fiyatı.
    | Ücretsiz anahtar: https://www.eia.gov/opendata/register.php
    */
    'eia' => [
        'key' => env('EIA_API_KEY'),
    ],
    /*
    | TCMB EVDS — Tüketici Fiyat Endeksi.
    | Ücretsiz anahtar: https://evds2.tcmb.gov.tr (üye ol -> profil -> API anahtarı)
    */
    /*
    | TCMB EVDS — Tüketici Fiyat Endeksi.
    | Ücretsiz anahtar: https://evds3.tcmb.gov.tr (üye ol -> profil -> API anahtarı)
    |
    | Taban adres yapılandırılabilir: servis 2026'da evds2'den evds3'e taşındı,
    | eski adres istekleri portala yönlendirip HTML döndürüyor.
    */
    'evds' => [
        'key'      => env('EVDS_API_KEY'),
        'base_url' => env('EVDS_BASE_URL', 'https://evds3.tcmb.gov.tr/service/evds/'),
    ],
];
