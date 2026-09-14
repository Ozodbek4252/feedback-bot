<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramPollState extends Model
{
    protected $fillable = [
        'bot',
        'last_update_id',
    ];
}
