<?php

namespace App\Http\Resources;

use App\Models\ExpressInterest;
use App\Models\ReportedUser;
use App\Models\Shortlist;
use App\Models\ViewGalleryImage;
use App\Models\ViewProfilePicture;
use App\Models\Religion;
use App\Models\MaritalStatus;
use Carbon\Carbon;
use App\Utility\MemberUtility;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        if ($this->member != null) {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $user = null;
            if ($token) {
                $user = $token->tokenable;
            }

            $profile_view_resquest_status = ViewProfilePicture::where('user_id', $this->id);
            if ($token) {
                $profile_view_resquest_status->where('requested_by', $user->id);
            }
            $profile_view_resquest_status->where('status', 1)->first();

            $gallery_view_resquest_status = ViewGalleryImage::where('user_id', $this->id);
            if ($token) {
                $gallery_view_resquest_status->where('requested_by', $user->id);
            }
            $gallery_view_resquest_status->where('status', 1)->first();

            $avatar_image = $this->member->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';

            $profile_picture_show = show_profile_picture($this);

            $package_update_alert = get_setting('full_profile_show_according_to_membership') == 1 && ($token &&  $user->membership) == 1 ? true : false;

            if ($token) {
                $shortlist = Shortlist::where('user_id', $this->id)->where('shortlisted_by', $user->id)->first();
            } else {
                $shortlist = Shortlist::where('user_id', $this->id)->first();
            }

            if ($token) {
                $do_interest = ExpressInterest::where('user_id', $this->id)->where('interested_by', $user->id)->first();
            } else {
                $do_interest = ExpressInterest::where('user_id', $this->id)->first();
            }

            if ($token) {
                $received_interest = ExpressInterest::where('interested_by', $this->id)->where('user_id', $user->id)->first();
            } else {
                $received_interest = ExpressInterest::where('interested_by', $this->id)->first();
            }

            $profile_reported = ReportedUser::where('user_id', $this->id);
            if ($token) {
                $profile_reported->where('reported_by', $user->id);
            }
            $profile_reported->first();

            $lang = $request->header('Accept-Language') ?? $request->header('app_language') ?? $request->get('lang') ?? 'ar';
            
            $religionName = '';
            if ($this->spiritual_backgrounds && !empty($this->spiritual_backgrounds->religion_id)) {
                $rel = Religion::find($this->spiritual_backgrounds->religion_id);
                if ($rel && !empty($rel->name)) {
                    $religionName = trim($rel->name);
                }
            }

            $maritalStatusName = '';
            if ($this->member && !empty($this->member->marital_status_id)) {
                $mStatus = MaritalStatus::find($this->member->marital_status_id);
                if ($mStatus && !empty($mStatus->name)) {
                    $maritalStatusName = trim($mStatus->name);
                }
            }

            if (function_exists('translate')) {
                if (!empty($religionName) && !in_array($religionName, ['_', '___'])) {
                    $tRel = translate($religionName, $lang);
                    if (!empty($tRel) && !in_array(trim($tRel), ['_', '___'])) {
                        $religionName = $tRel;
                    }
                }
                if (!empty($maritalStatusName) && !in_array($maritalStatusName, ['_', '___'])) {
                    $tMar = translate($maritalStatusName, $lang);
                    if (!empty($tMar) && !in_array(trim($tMar), ['_', '___'])) {
                        $maritalStatusName = $tMar;
                    }
                }
            }

            if (in_array(trim($religionName), ['_', '___', '-'])) $religionName = '';
            if (in_array(trim($maritalStatusName), ['_', '___', '-'])) $maritalStatusName = '';

            return [
                'user_id'              => $this->id,
                'code'                 => $this->code,
                'membership'           => $this->membership,
                'name'                 => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
                'photo'                => $profile_picture_show ? uploaded_asset($this->photo) : static_asset($avatar_image),
                'age'                  => !empty($this->member->birthday) ? Carbon::parse($this->member->birthday)->age : '',
                'country'              => MemberUtility::member_country($this->id),
                'height'               => !empty($this->physical_attributes->height) ? $this->physical_attributes->height : '',
                'religion'             => $religionName,
                'marital_status'       => $maritalStatusName,
                'maritial_status'      => $maritalStatusName,
                'package_update_alert' => $package_update_alert,
                'interest_status'      => ($do_interest ? 'sent interest' : $received_interest) ? 'received interest' : 'no interest',
                'shortlist_status'     => $shortlist ? 1 : 0,
                'report_status'        => $profile_reported ? true : false,
                'profile_view_resquest_status'   => $profile_view_resquest_status ? true : false,
                'gallery_view_resquest_status'   => $gallery_view_resquest_status ? true : false,
            ];
        }
        return [];
    }
}