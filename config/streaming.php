<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fournisseur streaming par défaut
    |--------------------------------------------------------------------------
    |
    | Valeurs supportées : aws_ivs, cloudflare, youtube
    | Peut être surchargé depuis l’admin (table settings, clé streaming_provider).
    |
    */
    'default' => env('STREAMING_PROVIDER', 'aws_ivs'),

    /*
    |--------------------------------------------------------------------------
    | AWS IVS (Interactive Video Service) — défaut
    |--------------------------------------------------------------------------
    */
    'aws_ivs' => [
        'access_key_id' => env('AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_IVS_REGION', env('AWS_DEFAULT_REGION', 'eu-west-1')),
        'channel_arn' => env('AWS_IVS_CHANNEL_ARN'),
        'playback_url' => env('AWS_IVS_PLAYBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Stream / Live
    |--------------------------------------------------------------------------
    */
    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_STREAM_API_TOKEN'),
        'live_input_uid' => env('CLOUDFLARE_LIVE_INPUT_UID'),
        'playback_video_uid' => env('CLOUDFLARE_STREAM_VIDEO_UID'),
        'playback_url' => env('CLOUDFLARE_STREAM_PLAYBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | YouTube — secours lecture (embed uniquement)
    |--------------------------------------------------------------------------
    |
    | YOUTUBE_LIVE_VIDEO_ID : identifiant ou URL ; ingest hors app (Studio / OBS → YouTube).
    | YOUTUBE_LIVE_EMBED_URL : iframe complète optionnelle (autoplay/mute fusionnés depuis la config).
    | YOUTUBE_EMBED_AUTOPLAY / YOUTUBE_EMBED_MUTED : lecture (autoplay true par défaut).
    |
    */
    'youtube' => [
        'video_id' => env('YOUTUBE_LIVE_VIDEO_ID'),
        'embed_url' => env('YOUTUBE_LIVE_EMBED_URL'),
        'embed_autoplay' => filter_var(env('YOUTUBE_EMBED_AUTOPLAY', true), FILTER_VALIDATE_BOOLEAN),
        'embed_muted' => filter_var(env('YOUTUBE_EMBED_MUTED', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
