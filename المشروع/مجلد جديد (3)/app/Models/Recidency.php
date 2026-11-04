<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recidency extends Model
{
    use SoftDeletes;

    /**
     * user_id is used by ProfileController::residency_info_update()
     * in Recidency::firstOrNew(['user_id' => auth()->id()]).
     * The remaining fields are updated explicitly by that controller.
     */
    protected $fillable = [
        'user_id',
        'birth_country_id',
        'growup_country_id',
        'recidency_country_id',
        'recidency_state_id',
        'recidency_city_id',
        'immigration_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
