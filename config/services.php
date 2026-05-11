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

    'webrtc' => [
        'stun_servers' => array_values(array_filter([
            env('WEBRTC_STUN_SERVER_1', 'stun:stun.l.google.com:19302'),
            env('WEBRTC_STUN_SERVER_2', 'stun:stun1.l.google.com:19302'),
            env('WEBRTC_STUN_SERVER_3', 'stun:stun2.l.google.com:19302'),
        ])),
        'turn_server' => env('WEBRTC_TURN_SERVER'),
        'turn_username' => env('WEBRTC_TURN_USERNAME'),
        'turn_credential' => env('WEBRTC_TURN_CREDENTIAL'),
    ],

    'agora' => [
        'app_id' => env('AGORA_APP_ID'),
        'app_certificate' => env('AGORA_APP_CERTIFICATE'),
    ],

];
