<?php

return [
    'line' => [
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
    ],
    'google' => [
        'calendar_id'          => env('GOOGLE_CALENDAR_ID', 'primary'),
        'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON', 'app/private/google-service-account.json'),
    ],
];
