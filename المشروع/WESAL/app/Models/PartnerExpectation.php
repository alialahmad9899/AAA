<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerExpectation extends Model
{
    use SoftDeletes;

    /**
     * user_id is required by ProfileController::partner_expectation_update()
     * when it initializes the row with firstOrNew(). The app updates each
     * preference explicitly after initialization.
     */
    protected $fillable = [
        'user_id',
        'general',
        'height',
        'weight',
        'min_height',
        'max_height',
        'min_weight',
        'max_weight',
        'marital_status_id',
        'children_acceptable',
        'residence_country_id',
        'religion_id',
        'caste_id',
        'sub_caste_id',
        'education',
        'profession',
        'smoking_acceptable',
        'drinking_acceptable',
        'diet',
        'body_type',
        'personal_value',
        'manglik',
        'language_id',
        'family_value_id',
        'preferred_country_id',
        'preferred_state_id',
        'complexion',
        'eye_color',
        'hair_color',
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

    public function member_language()
    {
        return $this->belongsTo(MemberLanguage::class, 'language_id')->withTrashed();
    }

    public function marital_status()
    {
        return $this->belongsTo(MaritalStatus::class)->withTrashed();
    }
}
