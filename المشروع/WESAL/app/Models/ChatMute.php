<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMute extends Model
{
    protected $guarded = [];

    protected $casts = [
        'muted_by' => 'integer',
        'muted_user_id' => 'integer',
    ];
}
