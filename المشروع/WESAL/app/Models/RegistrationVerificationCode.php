<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationVerificationCode extends Model
{
    use HasFactory;
    protected $fillable = ['email', 'phone', 'code', 'code_hash', 'is_verified', 'expires_at'];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
