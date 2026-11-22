<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpressInterestResource extends JsonResource
{
    public function toArray($request)
    {
        // Controller eager-loads interestedBy/member/religion/present-address/country.
        $interestedBy = $this->interestedBy;
        if (!$interestedBy) {
            return [];
        }

        $member = $interestedBy->member;
        $packageUpdateAlert = get_setting('full_profile_show_according_to_membership') == 1
            && auth()->user()->membership == 1;
        $gender = optional($member)->gender ?? 1;
        $defaultImage = $gender == 1
            ? static_asset('assets/img/avatar-place.png')
            : static_asset('assets/img/female-avatar-place.png');

        $birthday = optional($member)->birthday;
        $age = ($birthday && $birthday != '0000-00-00')
            ? Carbon::parse($birthday)->age
            : 0;

        $religion = get_setting('member_spiritual_and_social_background_section') == 'on'
            ? (optional(optional($interestedBy->spiritual_backgrounds)->religion)->name ?? '')
            : '';
        $presentAddress = $interestedBy->addresses->first();
        $country = get_setting('member_present_address_section') == 'on'
            ? (optional(optional($presentAddress)->country)->name ?? '')
            : '';
        $motherTongue = get_setting('member_language_section') == 'on'
            ? (optional(optional($member)->mothereTongue)->name ?? '')
            : '';

        return [
            'id'                   => $this->id,
            'interest_id'          => $this->id,
            'user_id'              => $this->interested_by,
            'package_update_alert' => $packageUpdateAlert,
            'photo'                => uploaded_asset($interestedBy->photo) ?? $defaultImage,
            'name'                 => trim(($interestedBy->first_name ?? '') . ' ' . ($interestedBy->last_name ?? '')),
            'age'                  => $age,
            'status'               => $this->status == 1 ? 'Approved' : 'Pending',
            'religion'             => $religion,
            'country'              => $country,
            'mothere_tongue'       => $motherTongue,
        ];
    }
}
