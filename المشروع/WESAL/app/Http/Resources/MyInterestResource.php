<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MyInterestResource extends JsonResource
{
    public function toArray($request)
    {
        // Controller eager-loads user/member/religion/present-address/country.
        $user = $this->user;
        if (!$user) {
            return [];
        }

        $member = $user->member;
        $packageUpdateAlert = get_setting('full_profile_show_according_to_membership') == 1
            && auth()->user()->membership == 1;
        $gender = optional($member)->gender ?? 1;
        $avatarImage = $gender == 1
            ? 'assets/img/avatar-place.png'
            : 'assets/img/female-avatar-place.png';
        $profilePictureShow = function_exists('show_profile_picture')
            ? show_profile_picture($user)
            : true;

        $birthday = optional($member)->birthday;
        $age = ($birthday && $birthday != '0000-00-00')
            ? Carbon::parse($birthday)->age
            : 0;

        $religion = get_setting('member_spiritual_and_social_background_section') == 'on'
            ? (optional(optional($user->spiritual_backgrounds)->religion)->name ?? '')
            : '';
        $presentAddress = $user->addresses->first();
        $country = get_setting('member_present_address_section') == 'on'
            ? (optional(optional($presentAddress)->country)->name ?? '')
            : '';
        $motherTongue = get_setting('member_language_section') == 'on'
            ? (optional(optional($member)->mothereTongue)->name ?? '')
            : '';

        return [
            'id'                   => $this->id,
            'interest_id'          => $this->id,
            'user_id'              => $this->user_id,
            'package_update_alert' => $packageUpdateAlert,
            'photo'                => $profilePictureShow ? uploaded_asset($user->photo) : static_asset($avatarImage),
            'name'                 => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'age'                  => $age,
            'religion'             => $religion,
            'country'              => $country,
            'mothere_tongue'       => $motherTongue,
            'status'               => $this->status == 1 ? 'Approved' : 'Pending',
        ];
    }
}
