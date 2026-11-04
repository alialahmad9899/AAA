<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Utility\MemberUtility;
use App\Models\ExpressInterest;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class MyInterestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $interest = ExpressInterest::find($this->id);
        if (!$interest) {
            return [];
        }

        $user = User::find($interest->user_id);
        if ($user != null) {
            $package_update_alert = get_setting('full_profile_show_according_to_membership') == 1 && auth()->user()->membership == 1 ? true : false;
            $gender = optional($user->member)->gender ?? 1;
            $avatar_image = $gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
            $profile_picture_show = function_exists('show_profile_picture') ? show_profile_picture($user) : true;

            $birthday = optional($user->member)->birthday;
            $age = ($birthday && $birthday != '0000-00-00') ? Carbon::parse($birthday)->age : 0;

            return [
                'id'                   => $this->id,
                'interest_id'          => $this->id,
                'user_id'              => $interest->user_id,
                'package_update_alert' => $package_update_alert,
                'photo'                => $profile_picture_show ? uploaded_asset($user->photo) : static_asset($avatar_image),
                'name'                 => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'age'                  => $age,
                'religion'             => MemberUtility::member_religion($interest->user_id),
                'country'              => MemberUtility::member_country($interest->user_id),
                'mothere_tongue'       => MemberUtility::member_mothere_tongue($interest->user_id),
                'status'               => $interest->status == 1 ? 'Approved' : 'Pending',
            ];
        }

        return [];
    }
}