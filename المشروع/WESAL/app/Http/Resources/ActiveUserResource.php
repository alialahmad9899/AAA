<?php

namespace App\Http\Resources;

use Illuminate\Support\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utility\MemberUtility;

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
        $countryName = optional(optional($this->address)->country)->name ?? '';
        $religionName = optional(optional($this->spiritual_backgrounds)->religion)->name ?? '';
        $motherTongueName = optional(optional($this->mothere_tongue)->mothere_tongue)->name ?? '';
        $maritalStatusName = optional(optional($this)->marital_status)->name ?? optional(optional($this->member)->marital_status)->name ?? '';
        $casteName = optional(optional($this->spiritual_backgrounds)->caste)->name;

        // 3. حساب نسبة التطابق الحقيقية للعضو الحالي بالنسبة للمستخدم المسجل
        $matchPercentage = 0;
        if (class_exists('App\Utility\MemberUtility') && method_exists('App\Utility\MemberUtility', 'member_match_percentage')) {
            // استدعاء دالة حساب نسبة التطابق الحقيقية من السيرفر
            $matchPercentage = MemberUtility::member_match_percentage($this->id);
        } elseif (isset($this->profile_match_percentage)) {
            $matchPercentage = $this->profile_match_percentage;
        } elseif (isset($this->match_percentage)) {
            $matchPercentage = $this->match_percentage;
        }

        return [
            'user_id'                  => $this->id,
            'code'                     => $this->code ?? '',
            'membership'               => $this->membership ?? 1,
            'first_name'               => $this->first_name ?? '',
            'last_name'                => $this->last_name ?? '',
            'photo'                    => $profile_picture_show && !empty($this->photo) ? uploaded_asset($this->photo) : static_asset($avatar_image),
            'age'                      => !empty(optional($this->member)->birthday) ? Carbon::parse($this->member->birthday)->age : 0,
            'country'                  => $countryName,
            'height'                   => optional($this->physical_attributes)->height ?? '',
            'religion'                 => $religionName,
            'mothere_tongue'           => $motherTongueName,
            'marital_status'           => $maritalStatusName,
            'caste'                    => $casteName ? $casteName . ', ' : '',
            'package_update_alert'     => $package_update_alert,
            'profile_match_percentage' => $matchPercentage, // <--- تمرير نسبة التطابق الحقيقية إلى التطبيق
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