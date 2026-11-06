<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageQuotaUsage extends Model
{
    protected $table = 'message_quota_usage';
    public $timestamps = false; //      

    protected $fillable = ['user_id', 'recipient_id', 'first_contact_at', 'last_message_at'];
}