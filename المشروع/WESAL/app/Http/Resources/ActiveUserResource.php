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
        $language = $request->header('Accept-Language')
            ?? $request->header('app_language')
            ?? $request->get('lang')
            ?? 'ar';
        $isEnglish = strtolower(substr((string) $language, 0, 2)) === 'en';
        $showPresentAddress = get_setting('member_present_address_section') == 'on';

        // Only use the explicit present address; never expose a permanent address
        // as though it were the member's current city or country.
        $presentAddress = $this->addresses
            ? $this->addresses->firstWhere('type', 'present')
            : null;
        $country = optional($presentAddress)->country;
        $city = optional($presentAddress)->city;
        $countryName = '';
        $residenceName = '';

        if ($showPresentAddress && $country) {
            $countryName = $isEnglish
                ? (trim((string) ($country->name_en ?? $country->name ?? '')))
                : (trim((string) ($country->name_ar ?? $country->name ?? '')));
        }
        if ($showPresentAddress && $city) {
            $residenceName = $isEnglish
                ? (trim((string) ($city->name ?? $city->name_en ?? '')))
                : (trim((string) ($city->name_ar ?? $city->name ?? '')));
        }
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
        $matchCriteriaCount = max(0, (int) ($this->match_criteria_count ?? 0));
        $matchEvaluatedCriteriaCount = max(0, (int) ($this->match_evaluated_criteria_count ?? 0));
        $matchStatus = $this->match_status ?? 'not_calculated';

        return [
            'user_id'                  => $this->id,
            'code'                     => $this->code ?? '',
            'membership'               => $this->membership ?? 1,
            'first_name'               => $this->first_name ?? '',
            'last_name'                => $this->last_name ?? '',
            'photo'                    => $profile_picture_show && !empty($this->photo) ? uploaded_asset($this->photo) : static_asset($avatar_image),
            'age'                      => $age ?? 0,
            'country'                  => $countryName,
            'residence'                => $residenceName,
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
            'match_criteria_count'     => $matchCriteriaCount,
            'match_evaluated_criteria_count' => $matchEvaluatedCriteriaCount,
            'match_status'             => $matchStatus,
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