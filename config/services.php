<?php

return [
    'line' => [
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
    ],
    'google' => [
        'client_id'            => env('GOOGLE_CLIENT_ID'),
        'client_secret'        => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri'         => env('GOOGLE_REDIRECT_URI'),
        'calendar_id'          => env('GOOGLE_CALENDAR_ID', 'primary'),
        'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON', 'app/private/google-service-account.json'),
    ],
];
