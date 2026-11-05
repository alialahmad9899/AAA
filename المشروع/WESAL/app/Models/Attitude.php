<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attitude extends Model
{
    use SoftDeletes;

    /** Fields written by the mobile profile API when initializing an attitude row. */
    protected $fillable = [
        'user_id',
        'affection',
        'humor',
        'political_views',
        'religious_service',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
