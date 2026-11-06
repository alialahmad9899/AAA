<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hobby extends Model
{
    use SoftDeletes;

    /** Fields written by the mobile profile API when initializing a hobbies row. */
    protected $fillable = [
        'user_id',
        'hobbies',
        'interests',
        'music',
        'books',
        'movies',
        'tv_shows',
        'sports',
        'fitness_activities',
        'cuisines',
        'dress_styles',
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
