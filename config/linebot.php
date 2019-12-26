<?php
return [
    'channel' => [
        'secret' => env('CHANNEL_SECRET'),
        'access_token' => env('CHANNEL_ACCESS_TOKEN'),
        'line_user_id' => env('LINE_USER_ID'),
    ],
    'url' => [
        'weather' => 'https://weather.com/zh-TW/weather/today/l/84937a912ce28623ee3e6b52266e46d2db0cd230da32e145281a14f3112d6098'
    ]
];
