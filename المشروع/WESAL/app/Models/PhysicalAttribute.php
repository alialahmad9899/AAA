<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhysicalAttribute extends Model
{
    use SoftDeletes;

    /**
     * Fields accepted for controlled Eloquent mass assignment.
     * user_id is required by ProfileController::physical_attributes_update()
     * when it calls PhysicalAttribute::firstOrNew(['user_id' => ...]).
     */
    protected $fillable = [
        'user_id',
        'height',
        'weight',
        'eye_color',
        'hair_color',
        'complexion',
        'blood_group',
        'body_type',
        'body_art',
        'disability',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
