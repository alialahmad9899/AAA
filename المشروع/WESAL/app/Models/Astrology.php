<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Astrology extends Model
{
    use SoftDeletes;

    /** Fields written by the mobile profile API when initializing an astrology row. */
    protected $fillable = [
        'user_id',
        'sun_sign',
        'moon_sign',
        'time_of_birth',
        'city_of_birth',
    ];


    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
