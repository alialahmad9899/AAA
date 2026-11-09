<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Utility\SubscriptionUtility;
use App\Models\Career;
use App\Models\Member;
use App\Models\Address;
use App\Models\IgnoredUser;
use App\Models\ReportedUser;
use Illuminate\Http\Request;
use App\Models\PhysicalAttribute;
use App\Models\SpiritualBackground;
use App\Services\SmartMatchingService;
use App\Utility\MemberUtility;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ActiveUserResource;
use App\Http\Resources\IgnoredUserResource;
use App\Http\Resources\PackageResource;
use App\Models\ExpressInterest;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Shortlist;
use App\Models\ViewGalleryImage;
use App\Models\ViewProfilePicture;
use Laravel\Sanctum\PersonalAccessToken;

class MemberController extends Controller
{
    /**
     * Attach sent/received interest state to visible profiles in one bounded query.
     */
    private function attachInterestStatusToProfiles($profiles, User $viewer)
    {
        $profiles = collect($profiles);
        $viewerId = (int) $viewer->id;
        $profileIds = $profiles->pluck('id')
            ->map(static function ($id) {
                return (int) $id;
            })
            ->filter(static function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values();

        if ($profileIds->isEmpty()) {
            return $profiles;
        }

        $interestRows = ExpressInterest::query()
            ->where(function ($query) use ($viewerId, $profileIds) {
                $query->where(function ($sent) use ($viewerId, $profileIds) {
                    $sent->where('interested_by', $viewerId)
                        ->whereIn('user_id', $profileIds);
                })->orWhere(function ($received) use ($viewerId, $profileIds) {
                    $received->where('user_id', $viewerId)
                        ->whereIn('interested_by', $profileIds);
                });
            })
            ->get(['user_id', 'interested_by']);

        $interestedIds = [];
        foreach ($interestRows as $interest) {
            $otherUserId = (int) $interest->interested_by === $viewerId
                ? (int) $interest->user_id
                : (int) $interest->interested_by;
            if ($otherUserId > 0) {
                $interestedIds[$otherUserId] = true;
            }
        }

        $profiles->each(function ($profile) use ($interestedIds) {
            $profile->setAttribute('is_interested', isset($interestedIds[(int) $profile->id]));
        });

        return $profiles;
    }

    /**
     * جلب أعضاء الجنس الآخر، مع تشغيل محرك التوافق الموحد عند تفعيل المطابقة الذكية.
     * لا نفرض اعتماداً إدارياً على مستخدمي الموبايل؛ نستبعد فقط الحسابات المحظورة/المعطلة.
     */
    public function matched_profile(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->member) {
            return ActiveUserResource::collection(collect([]))->additional([
                'result' => true,
            ]);
        }

        $matchEnabled = filter_var(
            $request->input('match_enabled', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $gender = (int) $user->member->gender;
        if (!in_array($gender, [1, 2], true)) {
            return ActiveUserResource::collection(collect([]))->additional([
                'result' => true,
            ]);
        }

        if ($matchEnabled) {
            if (!MemberUtility::can_auto_match($user->id)) {
                return response()->json([
                    'result' => false,
                    'status' => 'matching_locked',
                    'message' => translate('Smart matching is not enabled for this account.'),
                ], 403);
            }

            $matches = app(SmartMatchingService::class)->getMatches($user);
            $matches = $this->attachInterestStatusToProfiles($matches, $user);

            return ActiveUserResource::collection($matches)->additional([
                'result' => true,
                'smart_matching' => true,
            ]);
        }

        $oppositeGender = $gender === 1 ? 2 : 1;
        $usersQuery = User::query()
            ->where('user_type', 'member')
            ->where('id', '!=', $user->id)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', function ($query) use ($oppositeGender) {
                $query->where('gender', $oppositeGender);
            })
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', $user->id);
            })
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('user_id', $user->id);
            })
            ->orderByDesc('created_at');

        // Calculate a live score for every visible profile, without changing
        // the default newest-first order. The switch controls sorting only.
        $members = app(SmartMatchingService::class)->scoreCandidates(
            $user,
            $usersQuery->get(),
            false
        );
        $members = $this->attachInterestStatusToProfiles($members, $user);

        return ActiveUserResource::collection($members)->additional([
            'result' => true,
            'smart_matching' => false,
        ]);
    }

    public function member_listing(Request $request)
    {
        $age_from       = ($request->age_from != null) ? $request->age_from : null;
        $age_to         = ($request->age_to != null) ? $request->age_to : null;
        $member_code    = ($request->member_code != null) ? $request->member_code : null;
        $marital_status = ($request->marital_status != null) ? $request->marital_status : null;
        $religion_id    = ($request->religion_id != null) ? $request->religion_id : null;
        $caste_id       = ($request->caste_id != null) ? $request->caste_id : null;
        $sub_caste_id   = ($request->sub_caste_id != null) ? $request->sub_caste_id : null;
        $mother_tongue  = ($request->mother_tongue != null) ? $request->mother_tongue : null;
        $profession     = ($request->profession != null) ? $request->profession : null;
        $country_id     = ($request->country_id != null) ? $request->country_id : null;
        $state_id       = ($request->state_id != null) ? $request->state_id : null;
        $city_id        = ($request->city_id != null) ? $request->city_id : null;
        $min_height     = ($request->min_height != null) ? $request->min_height : null;
        $max_height     = ($request->max_height != null) ? $request->max_height : null;
        $member_type    = ($request->member_type != null) ? $request->member_type : 0;

        $users_query = User::query();
        $users_query->orderBy('created_at', 'desc')
            ->where('user_type', 'member')
            ->where('id', '!=', auth()->user()->id)
            ->where('blocked', 0)
            ->where('deactivated', 0);

        // Gender Check
        $user_ids = Member::where('gender', '!=', auth()->user()->member->gender)->pluck('user_id')->toArray();
        $users_query->whereIn('id', $user_ids);

        // Ignored member and ignored by member check
        $users_query->whereNotIn("id", function ($query) {
            $query->select('user_id')
                ->from('ignored_users')
                ->where('ignored_by', auth()->user()->id)->orWhere('user_id', auth()->user()->id);
        })
            ->whereNotIn("id", function ($query) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('ignored_by', auth()->user()->id)->orWhere('user_id', auth()->user()->id);
            });

        // Membership Check
        if ($member_type == 1 || $member_type == 2) {
            $users_query->where('membership', $member_type);
        }

        // Sort By age
        if (!empty($age_from)) {
            $age = $age_from + 1;
            $start = date('Y-m-d', strtotime("- $age years"));
            $user_ids = Member::where('birthday', '<=', $start)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users_query->whereIn('id', $user_ids);
            }
        }
        if (!empty($age_to)) {
            $age = $age_to + 1;
            $end = date('Y-m-d', strtotime("- $age years +1 day"));
            $user_ids = Member::where('birthday', '>=', $end)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users_query->whereIn('id', $user_ids);
            }
        }

        // Search by Member Code
        if (!empty($member_code)) {
            $users_query->where('code', $member_code);
        }

        // Sort by Marital Status
        if ($marital_status != null) {
            $user_ids = Member::where('marital_status_id', $marital_status)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users_query->whereIn('id', $user_ids);
            }
        }

        // Sort By religion
        if (!empty($sub_caste_id)) {
            $user_ids = SpiritualBackground::where('sub_caste_id', $sub_caste_id)->pluck('user_id')->toArray();
            $users_query->whereIn('id', $user_ids);
        } elseif (!empty($caste_id)) {
            $user_ids = SpiritualBackground::where('caste_id', $caste_id)->pluck('user_id')->toArray();
            $users_query->whereIn('id', $user_ids);
        } elseif (!empty($religion_id)) {
            $user_ids = SpiritualBackground::where('religion_id', $religion_id)->pluck('user_id')->toArray();
            $users_query->whereIn('id', $religion_id);
        }

        // Sort By Profession
        if (!empty($profession)) {
            $user_ids = Career::where('designation', 'like', '%' . $profession . '%')->pluck('user_id')->toArray();
            $users_query->whereIn('id', $user_ids);
        }

        // Sort By location
        if (!empty($city_id)) {
            $user_ids = Address::where('city_id', $city_id)->pluck('user_id')->toArray();
            $users_query->whereIn('id', $user_ids);
        } elseif (!empty($state_id)) {
            $user_ids = Address::where('state_id', $state_id)->pluck('user_id')->toArray();
            $users_query->whereIn('id', $state_id);
        } elseif (!empty($country_id)) {
            $user_ids = Address::where('country_id', $country_id)->pluck('user_id')->toArray();
            $users_query->whereIn('id', $user_ids);
        }

        // Sort By Mother Tongue
        if ($mother_tongue != null) {
            $user_ids = Member::where('mothere_tongue', $mother_tongue)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users_query->whereIn('id', $user_ids);
            }
        }

        // Sort by Height
        if (!empty($min_height)) {
            $user_ids = PhysicalAttribute::where('height', '>=', $min_height)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users_query->whereIn('id', $user_ids);
            }
        }
        if (!empty($max_height)) {
            $user_ids = PhysicalAttribute::where('height', '<=', $max_height)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users_query->whereIn('id', $user_ids);
            }
        }

        $users_query = $users_query->get();

        $data['members'] = ActiveUserResource::collection($users_query);
        $data['age_from'] = $age_from;
        $data['age_to'] = $age_to;
        $data['member_code'] = $member_code;
        $data['marital_status'] = $marital_status;
        $data['religion_id'] = $religion_id;
        $data['caste_id'] = $caste_id;
        $data['sub_caste_id'] = $sub_caste_id;
        $data['mother_tongue'] = $mother_tongue;
        $data['profession'] = $profession;
        $data['country_id'] = $country_id;
        $data['state_id'] = $state_id;
        $data['city_id'] = $city_id;
        $data['min_height'] = $min_height;
        $data['max_height'] = $max_height;
        $data['member_type'] = $member_type;

        return $this->response_data($data);
    }

    public function package_details()
    {
        $user = auth()->user();
        SubscriptionUtility::deactivateIfInvalid($user);
        $user->refresh();

        // Never return an expired, disabled, soft-deleted or free plan as the member's active package.
        $package = SubscriptionUtility::currentActivePaidPackage($user);
        return new PackageResource($package);
    }

    public function ignored_user_list()
    {
        return IgnoredUserResource::collection(IgnoredUser::where('ignored_by', auth()->user()->id)->latest()->paginate(10))->additional([
            'result' => true
        ]);
    }

    public function add_to_ignore_list(Request $request)
    {
        if (User::find($request->user_id)) {
            try {
                IgnoredUser::create($request->only('user_id') + [
                    'ignored_by' => auth()->user()->id
                ]);

                return $this->success_message('You have ignored this member');
            } catch (\Throwable $th) {
                return $this->failure_message('Something went wrong');
            }
        }
        return $this->failure_message('Invalid Member to ignore.');
    }

    public function remove_from_ignored_list(Request $request)
    {
        $ignored_user = IgnoredUser::where('user_id', $request->user_id)->where('ignored_by', auth()->user()->id)->first();
        if ($ignored_user) {
            IgnoredUser::destroy($ignored_user->id);
            return $this->success_message('You have removed this member from your ignored list');
        }
        return $this->failure_message('Something went wrong');
    }

    public function report_member(Request $request)
    {
        $targetUserId = $request->user_id ?? $request->id;

        if (!$targetUserId || !User::find($targetUserId)) {
            return response()->json([
                'result'  => false,
                'message' => 'عضو غير صالح للإبلاغ عنه.'
            ], 200);
        }

        $reportedUser = ReportedUser::where('user_id', $targetUserId)
            ->where('reported_by', auth()->user()->id)
            ->first();

        if ($reportedUser) {
            return response()->json([
                'result'  => false,
                'message' => 'لقد سبق وأن بلغت على هذا الشخص من قبل.'
            ], 200);
        }

        ReportedUser::create([
            'user_id'     => $targetUserId,
            'reported_by' => auth()->user()->id,
            'reason'      => $request->reason ?? $request->report_reason ?? 'بدون سبب محدد',
        ]);

        return response()->json([
            'result'  => true,
            'message' => 'تم إرسال بلاغك بنجاح وسيتولى الفريق مراجعته.'
        ], 200);
    }

    public function update_account_deactivation_status(Request $request)
    {
        $user = User::findOrFail(auth()->user()->id);
        $user->deactivated = $request->deacticvation;
        $user->save();

        $deacticvation_msg = $request->deacticvation == 1 ? translate('deactivated') : translate('reactivated');
        return $this->success_message('Your account ' . $deacticvation_msg . ' successfully!');
    }

    public function member_info($id)
    {
        $data = array();

        $shortlist = Shortlist::where('user_id', $id)->where('shortlisted_by', auth()->id())->first();
        $profile_reported = ReportedUser::where('user_id', $id)->where('reported_by', auth()->id())->first();
        $profile_view_resquest_status = ViewProfilePicture::where('user_id', $id)->where('requested_by', auth()->id())->where('status', 1)->first();
        $gallery_view_resquest_status = ViewGalleryImage::where('user_id', $id)->where('requested_by', auth()->id())->where('status', 1)->first();
        $do_interest = ExpressInterest::where('user_id', $id)->where('interested_by', auth()->id())->first();
        $received_interest = ExpressInterest::where('user_id', auth()->id())->where('interested_by', $id)->first();

        $data['interest_status']      = ($do_interest ? 'sent interest' : $received_interest) ? 'received interest' : 'no interest';
        $data['shortlist_status']    = $shortlist ? 1 : 0;
        $data['report_status']        = $profile_reported ? true : false;
        $data['profile_view_resquest_status']   = $profile_view_resquest_status ? true : false;
        $data['gallery_view_resquest_status']   = $gallery_view_resquest_status ? true : false;

        return $this->response_data($data);
    }

    public function member_validate(Request $request)
    {
        $false_response = [
            "result" => false,
            "user" =>   [
                'member_name' => "",
                'member_email' => "",
                'member_photo' => "",
                'remaining_interest' => "",
                'remaining_contact_view' => "",
                'remaining_photo_gallery' => "",
                'remaining_profile_image_view' => "",
                'remaining_gallery_image_view' => "",
                'current_package_info' => ""
            ]
        ];

        $token = PersonalAccessToken::findToken($request->bearerToken());
        if (!$token) {
            return response()->json($false_response);
        }

        $user = $token->tokenable;
        if ($user == null) {
            return response()->json($false_response);
        }

        SubscriptionUtility::deactivateIfInvalid($user);
        $user->refresh();

        $data['member_name'] = $user->first_name . ' ' . $user->last_name;
        $data['member_email'] = $user->email;
        $data['member_photo'] = uploaded_asset($user->photo) !== null ? uploaded_asset($user->photo) : static_asset('assets/img/avatar-place.png');
        $data['remaining_interest'] = get_remaining_package_value($user->id, 'remaining_interest');
        $data['remaining_contact_view'] = get_remaining_package_value($user->id, 'remaining_contact_view');
        $data['remaining_photo_gallery'] = get_remaining_package_value($user->id, 'remaining_photo_gallery');
        $data['remaining_profile_image_view'] = (get_setting('profile_picture_privacy') == 'only_me') ? get_remaining_package_value($user->id, 'remaining_profile_image_view') : '';
        $data['remaining_gallery_image_view'] = (get_setting('gallery_image_privacy') == 'only_me') ? get_remaining_package_value($user->id, 'remaining_gallery_image_view') : '';

        $activePackage = SubscriptionUtility::currentActivePaidPackage($user);
        $current_package_info = array(
            'package_id' => $activePackage ? $activePackage->id : null,
            'package_name' => $activePackage ? $activePackage->name : '',
            'package_expiry' => $activePackage ? date('d.m.Y', strtotime($user->member->package_validity)) : translate('Expired'),
            'is_premium' => (bool) $activePackage,
        );
        $data['current_package_info'] = $current_package_info;

        return $this->response_data($data);
    }

    public function getVerifyForm()
    {
        $user = auth()->user();
        if ($user->verification_info == null) {
            $formData = get_setting('verification_form');
            return response()->json(json_decode($formData));
        } else {
            return $this->failure_message(translate('Sorry! You have sent verification request already.'));
        }
    }

    public function isApproved(){
        $user = User::where('id',  auth()->user()->id)->first();
        $verification_info = false;
        if($user->verification_info != null){
            $verification_info = true;
        }
        return response()->json(['is_approved' => $user->approved, 'verification_info' => $verification_info]);
    }

    public function store_verification_info(Request $request)
    {
        $data = array();
        $i = 0;
        foreach (json_decode(Setting::where('type', 'verification_form')->first()->value) as $key => $element) {
            $item = array();
            if ($element->type == 'text') {
                $item['type'] = 'text';
                $item['label'] = $element->label;
                $item['value'] = $request['element_' . $i];
            } elseif ($element->type == 'select' || $element->type == 'radio') {
                $item['type'] = 'select';
                $item['label'] = $element->label;
                $item['value'] = $request['element_' . $i];
            } elseif ($element->type == 'multi_select') {
                $item['type'] = 'multi_select';
                $item['label'] = $element->label;
                $item['value'] = json_encode(explode(',', $request['element_' . $i]));
            } 
            elseif ($element->type == 'file') {
                $item['type'] = 'file';
                $item['label'] = $element->label;
                $item['value'] = $request['element_' . $i]->store('uploads/verification_form');
            }
            array_push($data, $item);
            $i++;
        }
        $user = auth()->user();
        $user->verification_info = json_encode($data);
        if ($user->save()) {
            return $this->success_message(translate('Your verification request has been submitted successfully!'));
        } 

        return $this->failure_message(translate('Something Wenr Wrong!'));
    }
}