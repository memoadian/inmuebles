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

    'mapbox' => [
        // Token público (pk.…): viaja al navegador, así que restringe su uso
        // por dominio desde el panel de Mapbox.
        'token' => env('MAPBOX_TOKEN'),
        'style' => env('MAPBOX_STYLE', 'mapbox://styles/mapbox/light-v11'),
        'style_picker' => env('MAPBOX_STYLE_PICKER', 'mapbox://styles/mapbox/streets-v12'),
    ],

    'nominatim' => [
        // Nominatim exige identificarse; usa un dominio/correo real del proyecto.
        'user_agent' => env('NOMINATIM_USER_AGENT', 'Ubiqa/1.0 (contacto@ubiqamx.com)'),
        'timeout' => (int) env('NOMINATIM_TIMEOUT', 10),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    ],

];
