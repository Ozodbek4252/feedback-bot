<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackMessage extends Model
{
    protected $fillable = [
        'bot',
        'telegram_message_id',
        'telegram_chat_id',
        'telegram_user_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'type',
        'text',
        'payload',
        'forwarded_message_id',
        'forwarded_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'forwarded_at' => 'datetime',
        ];
    }
}
