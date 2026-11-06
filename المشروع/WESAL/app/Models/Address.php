<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    /**
     * user_id and type are the lookup attributes passed to firstOrNew()
     * when the API synchronizes the user's present address after residency save.
     */
    protected $fillable = [
        'user_id',
        'type',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function country()
    {
        return $this->belongsTo(Country::class)->withTrashed();
    }

    public function state()
    {
        return $this->belongsTo(State::class)->withTrashed();
    }

    public function city()
    {
        return $this->belongsTo(City::class)->withTrashed();
    }
}
