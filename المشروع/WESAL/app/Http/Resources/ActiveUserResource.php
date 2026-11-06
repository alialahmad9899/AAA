<?php

namespace App\Http\Resources;

use Illuminate\Support\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveUserResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة JSON مسرّعة ومحتوية على نسبة التطابق الحقيقية
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // 1. تحديد الجنس وصورة الحساب الافتراضية
        $gender = optional($this->member)->gender;
        $avatar_image = ($gender == 1) ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
        
        $profile_picture_show = function_exists('show_profile_picture') ? show_profile_picture($this) : true;
        $package_update_alert = (get_setting('full_profile_show_according_to_membership') == 1 && optional(auth()->user())->membership == 1);

        // 2. جلب أسماء النصوص المباشرة
        $presentAddress = $this->addresses
            ? ($this->addresses->firstWhere('type', 'present') ?? $this->addresses->first())
            : null;
        $countryName = optional(optional($presentAddress)->country)->name ?? '';
        $occupation = '';
        $careers = $this->career;
        if ($careers) {
            $currentCareer = $careers->firstWhere('present', 1) ?? $careers->first();
            $occupation = optional($currentCareer)->designation ?? '';
        }
        $age = null;
        $birthday = optional($this->member)->birthday;
        if ($birthday) {
            try {
                $age = Carbon::parse($birthday)->age;
            } catch (\Throwable $exception) {
                $age = null;
            }
        }
        $religionName = optional(optional($this->spiritual_backgrounds)->religion)->name ?? '';
        $motherTongueName = optional(optional($this->member)->mothereTongue)->name ?? '';
        $maritalStatusName = optional(optional($this)->marital_status)->name ?? optional(optional($this->member)->marital_status)->name ?? '';
        $casteName = optional(optional($this->spiritual_backgrounds)->caste)->name;

        // تُضاف النسبة إلى نموذج المرشح من SmartMatchingService فقط.
        // قوائم التصفح العامة لا تعرض نسبة غير محسوبة.
        $matchPercentage = isset($this->profile_match_percentage)
            ? max(0, min(100, (int) $this->profile_match_percentage))
            : null;
        $matchDetails = isset($this->match_details) ? $this->match_details : null;

        return [
            'user_id'                  => $this->id,
            'code'                     => $this->code ?? '',
            'membership'               => $this->membership ?? 1,
            'first_name'               => $this->first_name ?? '',
            'last_name'                => $this->last_name ?? '',
            'photo'                    => $profile_picture_show && !empty($this->photo) ? uploaded_asset($this->photo) : static_asset($avatar_image),
            'age'                      => $age ?? 0,
            'country'                  => $countryName,
            'occupation'               => $occupation,
            'profession'               => $occupation,
            'height'                   => optional($this->physical_attributes)->height ?? '',
            'religion'                 => $religionName,
            'mothere_tongue'           => $motherTongueName,
            'marital_status'           => $maritalStatusName,
            'caste'                    => $casteName ? $casteName . ', ' : '',
            'package_update_alert'     => $package_update_alert,
            'profile_match_percentage' => $matchPercentage,
            'match_details'            => $matchDetails,
            'interest_status'          => 0,
            'interest_text'            => 'اهتمام',
            'shortlist_status'         => 0,
            'shortlist_text'           => 'المفضلة',
            'report_status'            => false,
            'profile_view_resquest_status' => false,
            'gallery_view_resquest_status' => false,
        ];
    }
}