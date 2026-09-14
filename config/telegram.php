<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feedback Bots
    |--------------------------------------------------------------------------
    |
    | Each entry is one Telegram bot that forwards messages from an app's
    | users into a Telegram group. To add a new app, add its bot token and
    | target group chat_id to .env and a matching entry below — no other
    | code changes are needed.
    |
    */

    'bots' => [

        'shelf' => [
            'name' => 'Shelf',
            'token' => env('TELEGRAM_SHELF_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_SHELF_BOT_CHAT_ID'),
        ],

        // 'cyclo' => [
        //     'name' => 'Cyclo',
        //     'token' => env('TELEGRAM_CYCLO_BOT_TOKEN'),
        //     'chat_id' => env('TELEGRAM_CYCLO_BOT_CHAT_ID'),
        // ],

    ],

];
