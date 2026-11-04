<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpiritualBackground extends Model
{
    use SoftDeletes;

    /** user_id is the firstOrNew lookup key in the API update action. */
    protected $fillable = [
        'user_id',
        'religion_id',
        'caste_id',
        'sub_caste_id',
        'ethnicity',
        'personal_value',
        'family_value_id',
        'community_value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function religion()
    {
        return $this->belongsTo(Religion::class)->withTrashed();
    }

    public function caste()
    {
        return $this->belongsTo(Caste::class)->withTrashed();
    }

    public function sub_caste()
    {
        return $this->belongsTo(SubCaste::class)->withTrashed();
    }

    public function family_value()
    {
        return $this->belongsTo(FamilyValue::class)->withTrashed();
    }
}
