<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\ProfileMatchController;
use App\Http\Requests\AstrologyRequest;
use App\Http\Requests\PartnerExpectationRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\CareerResource;
use App\Http\Resources\EducationResource;
use App\Http\Resources\GalleryImage\RequestedGalleryImage;
use App\Http\Resources\GalleryImageResource;
use App\Http\Resources\MatchedProfileResource;
use App\Http\Resources\Profile\LanguageResource;
use App\Http\Resources\PublicProfile\AboutUser;
use App\Http\Resources\PublicProfile\AddressResource;
use App\Http\Resources\PublicProfile\AstronomicInformation;
use App\Http\Resources\PublicProfile\AttitudesBehaviors;
use App\Http\Resources\PublicProfile\BasicInformation;
use App\Http\Resources\PublicProfile\PublicBasicInformation;
use App\Http\Resources\PublicProfile\FamilyInformation;
use App\Http\Resources\PublicProfile\HobbiesInterests;
use App\Http\Resources\PublicProfile\LifeStyleResource;
use App\Http\Resources\PublicProfile\PartnerExpectationResource;
use App\Http\Resources\PublicProfile\PhysicalAttributes;
use App\Http\Resources\PublicProfile\ResidenceInformation;
use App\Http\Resources\PublicProfile\SpiritualSocialBackground;
use App\Models\Address;
use App\Models\Astrology;
use App\Models\Attitude;
use App\Models\Career;
use App\Models\Caste;
use App\Models\ChatThread;
use App\Models\City;
use App\Models\Country;
use App\Models\Education;
use App\Models\ExpressInterest;
use App\Models\Family;
use App\Models\FamilyValue;
use App\Models\GalleryImage;
use App\Models\HappyStory;
use App\Models\Hobby;
use App\Models\IgnoredUser;
use App\Models\Lifestyle;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\MemberLanguage;
use App\Models\OnBehalf;
use App\Models\PackagePayment;
use App\Models\PartnerExpectation;
use App\Models\PhysicalAttribute;
use App\Models\ProfileMatch;
use App\Models\ProfileViewer;
use App\Models\Recidency;
use App\Models\Religion;
use App\Models\ReportedUser;
use App\Models\Setting;
use App\Models\Shortlist;
use App\Models\SpiritualBackground;
use App\Models\Staff;
use App\Models\State;
use App\Models\SubCaste;
use App\Models\ViewContact;
use App\Models\ViewGalleryImage;
use App\Models\Upload;
use App\Models\User;
use App\Models\ViewProfilePicture;
use App\Utility\SubscriptionUtility;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;
use App\Utility\MemberUtility;

class ProfileController extends Controller
{
    /**
     * Build owner-visible basic information without applying public photo privacy
     * rules. This endpoint is authenticated as the profile owner via Sanctum.
     */
    private function owner_basic_information(User $user): array
    {
        $data = [];
        try {
            $data = (new BasicInformation($user))->toArray(request());
        } catch (\Throwable $e) {
            \Log::warning('Owner basic profile resource fallback used: ' . $e->getMessage());
        }

        $photoUrl = !empty($user->photo) ? uploaded_asset($user->photo) : null;
        if (!$photoUrl && !empty($user->avatar_original)) {
            $photoUrl = uploaded_asset($user->avatar_original);
        }
        if (!$photoUrl && !empty($user->avatar)) {
            $avatarPath = trim((string) $user->avatar);
            $photoUrl = filter_var($avatarPath, FILTER_VALIDATE_URL)
                ? $avatarPath
                : static_asset(ltrim($avatarPath, '/'));
        }
        if (!$photoUrl) {
            $photoUrl = static_asset('assets/img/avatar-place.png');
        }

        // Keep both spellings consumed by current and older Flutter builds.
        $data['firs_name'] = $user->first_name ?? '';
        $data['first_name'] = $user->first_name ?? '';
        $data['last_name'] = $user->last_name ?? '';
        $data['name'] = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $data['photo'] = $photoUrl;
        $data['photo_id'] = $user->photo ?? null;

        return $data;
    }

    /**
     * دالة جلب كافة بيانات الملف الشخصي للمستخدم الحالي دفعة واحدة لسرعة فائقة
     */
    public function get_full_my_profile()
    {
        $userId = auth()->id();
        SubscriptionUtility::deactivateIfInvalid(auth()->user());

        // جلب المستخدم وكافة الجداول والروابط بقاعدة البيانات بطلب واحد (Eager Loading)
        $user = User::with([
            'member',
            'education',
            'career',
            'physical_attributes',
            'hobbies',
            'attitude',
            'recidency',
            'spiritual_backgrounds',
            'lifestyles',
            'astrologies',
            'families',
            'partner_expectations'
        ])->find($userId);

        if (!$user) {
            return $this->failure_message('User Not Found');
        }

        $member_known_languages = null;
        $member_mother_tongue = null;
        if ($user->member) {
            $known_languages = json_decode($user->member->known_languages);
            $mother_tongue = json_decode($user->member->mothere_tongue);
            if ($known_languages != null) {
                $member_known_languages = LanguageResource::collection(MemberLanguage::whereIn('id', $known_languages)->get());
            }
            if ($mother_tongue != null) {
                $member_mother_tongue = new LanguageResource(MemberLanguage::where('id', $mother_tongue)->first());
            }
        }

        // Keep contact fields private to the owner; retired fields are not returned.
        $ownerLifestyle = null;
        if ($user->lifestyles) {
            $ownerLifestyle = (new LifeStyleResource($user->lifestyles))->toArray(request());
            unset($ownerLifestyle['diet']);
        }

        $data = [
            // Return the authenticated owner ID so Flutter can recover its local session metadata.
            'user_id'              => $user->id,
            'intoduction'          => new AboutUser($user),
            'basic_info'           => $this->owner_basic_information($user),
            'contact_details'      => [
                'email' => $user->email,
                'phone' => $user->phone
            ],
            'education'            => $user->education ? EducationResource::collection($user->education) : [],
            'career'               => $user->career ? CareerResource::collection($user->career) : [],
            'physical_attributes'  => $user->physical_attributes ? new PhysicalAttributes($user->physical_attributes) : null,
            'known_languages'      => $member_known_languages,
            'mother_tongue'        => $member_mother_tongue,
            'hobbies_interest'     => $user->hobbies ? new HobbiesInterests($user->hobbies) : null,
            'attitude_behavior'    => $user->attitude ? new AttitudesBehaviors($user->attitude) : null,
            'residence_info'       => $user->recidency ? new ResidenceInformation($user->recidency) : null,
            'spiritual_backgrounds'=> $user->spiritual_backgrounds ? new SpiritualSocialBackground($user->spiritual_backgrounds) : null,
            'lifestyles'           => $ownerLifestyle,
            'astrologies'          => $user->astrologies ? new AstronomicInformation($user->astrologies) : null,
            'present_address'      => Address::where('user_id', $userId)->where('type', 'present')->first() ? new AddressResource(Address::where('user_id', $userId)->where('type', 'present')->first()) : null,
            'permanent_address'    => Address::where('user_id', $userId)->where('type', 'permanent')->first() ? new AddressResource(Address::where('user_id', $userId)->where('type', 'permanent')->first()) : null,
            'partner_expectation'  => $user->partner_expectations ? new PartnerExpectationResource($user->partner_expectations) : null,
        ];

        return $this->response_data($data);
    }

    public function profile_settings()
    {
        $member             = User::findOrFail(auth()->user()->id);
        $countries          = Country::where('status', 1)->get();
        $states             = State::all();
        $cities             = City::all();
        $religions          = Religion::all();
        $castes             = Caste::all();
        $sub_castes         = SubCaste::all();
        $family_values      = FamilyValue::all();
        $marital_statuses   = MaritalStatus::all();
        $on_behalves        = OnBehalf::all();
        $languages          = MemberLanguage::all();

        return response()->json([
            'result' => true,
            'member' => $member, 'countries' => $countries, 'states' => $states, 'cities' => $cities,
            'religions' => $religions, 'castes' => $castes, 'sub_castes' => $sub_castes, 'family_values' => $family_values, 'marital_statuses' => $marital_statuses, 'on_behalves' => $on_behalves, 'languages' => $languages,
        ]);
    }

    public function get_introduction()
    {
        return (new AboutUser(auth()->user()))->additional([
            'result' => true
        ]);
    }

    public function get_email()
    {
        $data['email'] = auth()->user()->email;
        return $this->response_data($data);
    }

    public function introduction_update(Request $request)
    {
        $member = Member::where('user_id', auth()->id())->first();
        $member->introduction = $request->introduction;
        $member->save();
        return $this->success_message('Introduction updated successfully!');
    }

    public function get_basic_info()
    {
        $user = auth()->user()->fresh();
        SubscriptionUtility::deactivateIfInvalid($user);

        return response()->json([
            'result' => true,
            'data' => $this->owner_basic_information($user),
        ]);
    }

    public function basic_info_update(ProfileRequest $request)
    {
        $storedPhotoPath = null;

        try {
            DB::transaction(function () use ($request, &$storedPhotoPath) {
                $user = User::lockForUpdate()->findOrFail(auth()->id());
                $member = Member::where('user_id', $user->id)->lockForUpdate()->first();

                if (!$member) {
                    throw new \RuntimeException('Member profile record is missing.');
                }

                $input = $request->all();

                foreach (['first_name', 'last_name', 'phone'] as $field) {
                    if (array_key_exists($field, $input)) {
                        $user->{$field} = $input[$field];
                    }
                }

                if ($request->hasFile('photo')) {
                    $uploadId = upload_api_file($request->file('photo'));
                    $upload = $uploadId ? Upload::find($uploadId) : null;

                    if (!$upload || empty($upload->file_name)) {
                        throw new \RuntimeException('Unable to persist the uploaded profile photo.');
                    }

                    $storedPhotoPath = $upload->file_name;
                    $user->photo = $upload->id;
                    // Keep profile photo updates compatible with installations that only have "photo".
                    if (\Schema::hasColumn('users', 'avatar_original')) {
                        $user->avatar_original = $upload->id;
                    }
                    // The deployed users table may not include the legacy avatar path column.
                    if (\Schema::hasColumn('users', 'avatar')) {
                        $user->avatar = $upload->file_name;
                    }

                    $approvalRequired = Setting::where('type', 'profile_picture_approval_by_admin')
                        ->value('value');
                    $user->photo_approved = $approvalRequired ? 0 : 1;
                }

                if (array_key_exists('gender', $input)) {
                    $member->gender = $input['gender'];
                }
                if (array_key_exists('on_behalf', $input)) {
                    $member->on_behalves_id = $input['on_behalf'];
                } elseif (array_key_exists('on_behalves_id', $input)) {
                    $member->on_behalves_id = $input['on_behalves_id'];
                }
                if (array_key_exists('date_of_birth', $input) && !empty($input['date_of_birth'])) {
                    $member->birthday = \Carbon\Carbon::parse($input['date_of_birth'])->format('Y-m-d');
                }
                if (array_key_exists('marital_status', $input)) {
                    $member->marital_status_id = $input['marital_status'];
                } elseif (array_key_exists('marital_status_id', $input)) {
                    $member->marital_status_id = $input['marital_status_id'];
                } elseif (array_key_exists('maritial_status_id', $input)) {
                    $member->marital_status_id = $input['maritial_status_id'];
                }
                if (array_key_exists('children', $input)) {
                    $member->children = $input['children'];
                }
                if (array_key_exists('introduction', $input)) {
                    $member->introduction = $input['introduction'];
                }

                $user->save();
                $member->save();
            });

            return $this->success_message('Member basic info has been updated successfully.');
        } catch (\Throwable $e) {
            if ($storedPhotoPath) {
                try {
                    \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))
                        ->delete($storedPhotoPath);
                } catch (\Throwable $cleanupError) {
                    \Log::warning('Profile photo cleanup failed: ' . $cleanupError->getMessage());
                }
            }

            \Log::error('Profile basic-info update failed: ' . $e->getMessage());

            return response()->json([
                'result' => false,
                'message' => translate('Unable to update profile information. Please try again.'),
            ], 500);
        }
    }

    public function present_address()
    {
        $present_address = Address::where('user_id', auth()->id())->where('type', 'present')->first();
        if ($present_address) {
            return (new AddressResource($present_address))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_data($present_address);
        }
    }

    public function permanent_address()
    {
        $permanent_address = Address::where('user_id', auth()->id())->where('type', 'permanent')->first();
        if ($permanent_address) {
            return (new AddressResource($permanent_address))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_data($permanent_address);
        }
    }

    public function address_update(Request $request)
    {
        $this->validate($request, [
            'country_id'   => ['required'],
            'state_id'     => ['required'],
            'city_id'      => ['required'],
            'postal_code'  => ['required', 'numeric'],
        ]);
        $address = Address::where('user_id', auth()->id())->where('type', $request->address_type)->first();
        if (empty($address)) {
            $address = new Address();
            $address->user_id = auth()->id();
        }
        $address->country_id   = $request->country_id;
        $address->state_id     = $request->state_id;
        $address->city_id      = $request->city_id;
        $address->postal_code  = $request->postal_code;
        $address->type         = $request->address_type;
        $address->save();
        return $this->success_message('Address info has been updated successfully');
    }

    public function physical_attributes()
    {
        if (auth()->user()->physical_attributes) {
            return (new PhysicalAttributes(auth()->user()->physical_attributes))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }

    public function physical_attributes_update(Request $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'height'      => ['sometimes', 'nullable', 'numeric', 'between:0.4,2.5'],
            'weight'      => ['sometimes', 'nullable', 'numeric', 'between:1,999.99'],
            'eye_color'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'eye_colour'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'hair_color'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'hair_colour' => ['sometimes', 'nullable', 'string', 'max:50'],
            'complexion'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'blood_group' => ['sometimes', 'nullable', 'string', 'max:10'],
            'body_type'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'body_art'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'disability'  => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $values = [];
        foreach (['height', 'weight', 'complexion', 'blood_group', 'body_type', 'body_art', 'disability'] as $field) {
            if (array_key_exists($field, $input)) {
                $values[$field] = $input[$field];
            }
        }
        if (array_key_exists('eye_color', $input) || array_key_exists('eye_colour', $input)) {
            $values['eye_color'] = $input['eye_color'] ?? $input['eye_colour'];
        }
        if (array_key_exists('hair_color', $input) || array_key_exists('hair_colour', $input)) {
            $values['hair_color'] = $input['hair_color'] ?? $input['hair_colour'];
        }

        if (empty($values)) {
            return response()->json([
                'result' => false,
                'message' => translate('No physical attributes were provided for update.'),
            ], 422);
        }

        $attribute = PhysicalAttribute::firstOrNew(['user_id' => auth()->id()]);
        foreach ($values as $field => $value) {
            $attribute->{$field} = $value;
        }
        $attribute->save();

        return $this->success_message('Physical Attribute Info has been updated successfully');
    }

    public function member_language()
    {
        $member_known_languages = null;
        $member_mother_tongue = null;
        $known_languages = json_decode(auth()->user()->member->known_languages);
        $mother_tongue = auth()->user()->member->mothere_tongue;
        if ($known_languages != null) {
            $member_known_languages = LanguageResource::collection(MemberLanguage::whereIn('id', $known_languages)->get());
        }
        if ($mother_tongue != null) {
            $member_mother_tongue =  new LanguageResource(MemberLanguage::where('id', $mother_tongue)->first());
        }
        $data['mother_tongue'] = $member_mother_tongue;
        $data['known_languages'] = $member_known_languages;
        return $this->response_data($data);
    }

    public function member_language_update(Request $request)
    {
        $member  = Member::where('user_id', auth()->id())->first();
        if ($member) {
            $member->mothere_tongue     = $request->mothere_tongue;
            $member->known_languages    = $request->known_languages;
            $member->save();
            return $this->success_message('Member language info has been updated successfully');
        }

        return $this->failure_message('You are not authorized');
    }

    public function hobbies_interest()
    {
        if (auth()->user()->hobbies) {
            return (new HobbiesInterests(auth()->user()->hobbies))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }

    public function hobbies_interest_update(Request $request)
    {
        $fields = [
            'hobbies', 'interests', 'music', 'books', 'movies',
            'tv_shows', 'sports', 'fitness_activities', 'cuisines', 'dress_styles',
        ];
        $input = $request->all();
        $providedFields = array_intersect($fields, array_keys($input));

        if (empty($providedFields)) {
            return response()->json([
                'result' => false,
                'message' => translate('No hobby or interest fields were provided.'),
            ], 422);
        }

        $hobbies = Hobby::firstOrNew(['user_id' => auth()->id()]);
        foreach ($providedFields as $field) {
            $hobbies->{$field} = $input[$field];
        }
        $hobbies->save();

        return $this->success_message('Hobby and Interests info has been updated successfully');
    }

    public function attitude_behavior()
    {
        if (auth()->user()->attitude) {
            return (new AttitudesBehaviors(auth()->user()->attitude))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }

    public function attitude_behavior_update(Request $request)
    {
        $fields = ['affection', 'humor', 'political_views', 'religious_service'];
        $input = $request->all();
        $providedFields = array_intersect($fields, array_keys($input));

        if (empty($providedFields)) {
            return response()->json([
                'result' => false,
                'message' => translate('No attitude or behavior fields were provided.'),
            ], 422);
        }

        $attitude = Attitude::firstOrNew(['user_id' => auth()->id()]);
        foreach ($providedFields as $field) {
            $attitude->{$field} = $input[$field];
        }
        $attitude->save();

        return $this->success_message('Personal Attitude and Behavior Info has been updated successfully');
    }

    /**
     * جلب معلومات الإقامة مع استعادة معرفات المحافظة والمدينة مباشرة
     */
    public function residency_info()
    {
        if (auth()->user()->recidency) {
            $rec = auth()->user()->recidency;
            $residenceData = (new ResidenceInformation($rec))->resolve(request());

            $stateId = $rec->recidency_state_id;
            $cityId  = $rec->recidency_city_id;

            return response()->json([
                'result' => true,
                'data'   => array_merge($residenceData, [
                    'recidency_state_id' => $stateId,
                    'residency_state_id' => $stateId,
                    'state_id'           => $stateId,
                    'recidency_city_id'  => $cityId,
                    'residency_city_id'  => $cityId,
                    'city_id'            => $cityId,
                ])
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }

    /**
     * تحديث معلومات الإقامة وتخزين المحافظة والمدينة وإنشاء سجل العنوان لضمان ظهور البلد وتطابقه
     */
    public function residency_info_update(Request $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'birth_country_id'      => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'recidency_country_id'  => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'residency_country_id'  => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'growup_country_id'     => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'recidency_state_id'    => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'residency_state_id'    => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'state_id'              => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'recidency_city_id'     => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'residency_city_id'     => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'city_id'               => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'immigration_status'    => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $residency = Recidency::firstOrNew(['user_id' => auth()->id()]);
        $oldCountryId = $residency->recidency_country_id;

        foreach (['birth_country_id', 'growup_country_id', 'immigration_status'] as $field) {
            if (array_key_exists($field, $input)) {
                $residency->{$field} = $input[$field];
            }
        }

        $countryWasProvided = array_key_exists('recidency_country_id', $input)
            || array_key_exists('residency_country_id', $input);
        $countryId = array_key_exists('recidency_country_id', $input)
            ? $input['recidency_country_id']
            : (array_key_exists('residency_country_id', $input)
                ? $input['residency_country_id']
                : $oldCountryId);

        if ($countryWasProvided) {
            $residency->recidency_country_id = $countryId;
        }

        $oldStateId = $residency->recidency_state_id;
        $stateWasProvided = array_key_exists('recidency_state_id', $input)
            || array_key_exists('residency_state_id', $input)
            || array_key_exists('state_id', $input);
        $stateId = $input['recidency_state_id']
            ?? $input['residency_state_id']
            ?? $input['state_id']
            ?? null;

        $countryChanged = $countryWasProvided && (string) $oldCountryId !== (string) $countryId;
        if ($stateWasProvided) {
            $residency->recidency_state_id = $stateId ?: null;
        } elseif ($countryChanged) {
            // A governorate selected under the old country must never survive a country change.
            $residency->recidency_state_id = null;
        }
        $effectiveStateId = $residency->recidency_state_id;
        $stateChanged = $stateWasProvided &&
            (string) $oldStateId !== (string) $effectiveStateId;

        $cityWasProvided = array_key_exists('recidency_city_id', $input)
            || array_key_exists('residency_city_id', $input)
            || array_key_exists('city_id', $input);
        $cityId = $input['recidency_city_id']
            ?? $input['residency_city_id']
            ?? $input['city_id']
            ?? null;

        if ($cityWasProvided) {
            $residency->recidency_city_id = $cityId ?: null;
        } elseif ($countryChanged || $stateChanged) {
            $residency->recidency_city_id = null;
        }
        $effectiveCityId = $residency->recidency_city_id;

        if ($effectiveStateId && !$countryId) {
            return response()->json([
                'result' => false,
                'message' => translate('A country must be selected before assigning a governorate.'),
            ], 422);
        }
        if ($effectiveCityId && !$effectiveStateId) {
            return response()->json([
                'result' => false,
                'message' => translate('A governorate must be selected before assigning a city.'),
            ], 422);
        }
        if ($effectiveStateId && $countryId &&
            !State::where('id', $effectiveStateId)->where('country_id', $countryId)->exists()) {
            return response()->json([
                'result' => false,
                'message' => translate('The selected governorate does not belong to the selected country.'),
            ], 422);
        }
        if ($effectiveCityId && $effectiveStateId &&
            !City::where('id', $effectiveCityId)->where('state_id', $effectiveStateId)->exists()) {
            return response()->json([
                'result' => false,
                'message' => translate('The selected city does not belong to the selected governorate.'),
            ], 422);
        }

        DB::transaction(function () use ($residency, $countryWasProvided, $countryId, $stateWasProvided, $cityWasProvided, $effectiveStateId, $effectiveCityId) {
            $residency->save();

            // Keep the present address synchronized only with an actual residence country.
            // Birth country is not a substitute for present residence.
            if ($countryWasProvided || $stateWasProvided || $cityWasProvided) {
                $effectiveCountryId = $countryId ?: $residency->recidency_country_id;
                if ($effectiveCountryId) {
                    $address = Address::firstOrNew([
                        'user_id' => auth()->id(),
                        'type' => 'present',
                    ]);
                    $address->country_id = $effectiveCountryId;
                    $address->state_id = $effectiveStateId ?: null;
                    $address->city_id = $effectiveCityId ?: null;
                    // Preserve the user's existing postal code; never invent one.
                    $address->save();
                }
            }
        });

        return $this->success_message('Residency Info has been updated successfully');
    }

    public function spiritual_background()
    {
        if (auth()->user()->spiritual_backgrounds) {
            $sp = auth()->user()->spiritual_backgrounds;
            $spiritualData = (new SpiritualSocialBackground($sp))->resolve(request());

            return response()->json([
                'result' => true,
                'data'   => array_merge($spiritualData, [
                    'family_value_id'     => $sp->family_value_id,
                    'raw_family_value_id' => $sp->family_value_id,
                ])
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }

    /**
     * تحديث الخلفية الروحية وحفظ الحالة الاجتماعية مباشرة في جدول الأعضاء الرئيسي
     */
    public function spiritual_background_update(Request $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'member_religion_id' => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            'religion_id'        => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            'member_caste_id'    => ['sometimes', 'nullable', 'integer', 'exists:castes,id'],
            'caste_id'           => ['sometimes', 'nullable', 'integer', 'exists:castes,id'],
            'member_sub_caste_id' => ['sometimes', 'nullable', 'integer', 'exists:sub_castes,id'],
            'sub_caste_id'        => ['sometimes', 'nullable', 'integer', 'exists:sub_castes,id'],
            'family_value_id'    => ['sometimes', 'nullable', 'integer', 'exists:family_values,id'],
            'marital_status'     => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'marital_status_id'  => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'member_marital_status_id' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'ethnicity'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'personal_value'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'community_value'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $spiritualMap = [
            'religion_id' => ['member_religion_id', 'religion_id'],
            'caste_id' => ['member_caste_id', 'caste_id'],
            'sub_caste_id' => ['member_sub_caste_id', 'sub_caste_id'],
            'ethnicity' => ['ethnicity'],
            'personal_value' => ['personal_value'],
            'family_value_id' => ['family_value_id'],
            'community_value' => ['community_value'],
        ];

        $hasSpiritualInput = false;
        foreach ($spiritualMap as $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $input)) {
                    $hasSpiritualInput = true;
                    break 2;
                }
            }
        }

        $maritalKeys = ['marital_status', 'member_marital_status_id', 'marital_status_id'];
        $hasMaritalInput = false;
        foreach ($maritalKeys as $key) {
            if (array_key_exists($key, $input)) {
                $hasMaritalInput = true;
                break;
            }
        }

        if (!$hasSpiritualInput && !$hasMaritalInput) {
            return response()->json([
                'result' => false,
                'message' => translate('No spiritual or marital information was provided.'),
            ], 422);
        }

        DB::transaction(function () use ($input, $spiritualMap, $hasSpiritualInput, $maritalKeys, $hasMaritalInput) {
            if ($hasSpiritualInput) {
                $spiritual = SpiritualBackground::firstOrNew(['user_id' => auth()->id()]);
                foreach ($spiritualMap as $column => $keys) {
                    foreach ($keys as $key) {
                        if (array_key_exists($key, $input)) {
                            $spiritual->{$column} = $input[$key];
                            break;
                        }
                    }
                }
                $spiritual->save();
            }

            if ($hasMaritalInput) {
                $member = Member::where('user_id', auth()->id())->first();
                if ($member) {
                    foreach ($maritalKeys as $key) {
                        if (array_key_exists($key, $input)) {
                            $member->marital_status_id = $input[$key];
                            break;
                        }
                    }
                    $member->save();
                }
            }
        });

        return $this->success_message('Spiritual Background info has been updated successfully');
    }

    public function life_style()
    {
        $lifestyle = auth()->user()->lifestyles;
        if (!$lifestyle) {
            return $this->failure_message('No Data Found!!');
        }

        $data = (new LifeStyleResource($lifestyle))->toArray(request());
        unset($data['diet']);
        return response()->json(['result' => true, 'data' => $data]);
    }

    public function life_style_update(Request $request)
    {
        $lifestyle = Lifestyle::where('user_id', auth()->id())->first();
        if (empty($lifestyle)) {
            $lifestyle = new Lifestyle;
            $lifestyle->user_id = auth()->id();
        }

        // Ignore retired diet input and preserve the historical database value.
        $input = $request->all();
        foreach (['drink', 'smoke', 'living_with'] as $field) {
            if (array_key_exists($field, $input)) {
                $lifestyle->{$field} = $input[$field];
            }
        }
        $lifestyle->save();
        return $this->success_message('Lifestyle info has been updated successfully');
    }

    public function astronomic_info()
    {
        if (auth()->user()->astrologies) {
            return (new AstronomicInformation(auth()->user()->astrologies))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_data(auth()->user()->astrologies);
        }
    }

    public function astronomic_info_update(AstrologyRequest $request)
    {
        $input = $request->validated();
        if (empty($input)) {
            return response()->json([
                'result' => false,
                'message' => translate('No astronomic fields were provided for update.'),
            ], 422);
        }

        $astrologies = Astrology::firstOrNew(['user_id' => auth()->id()]);
        foreach (['sun_sign', 'moon_sign', 'time_of_birth', 'city_of_birth'] as $field) {
            if (array_key_exists($field, $input)) {
                $astrologies->{$field} = $input[$field];
            }
        }

        $astrologies->save();
        return $this->success_message('Astronomic Info has been updated successfully');
    }

    public function family_info()
    {
        $family = auth()->user()->families;
        if (!$family) {
            return $this->failure_message('No Data Found!!');
        }

        $data = (new FamilyInformation($family))->toArray(request());
        unset($data['father'], $data['mother']);
        return response()->json(['result' => true, 'data' => $data]);
    }

    public function family_info_update(Request $request)
    {
        $family = Family::where('user_id', auth()->id())->first();
        if (empty($family)) {
            $family = new Family;
            $family->user_id = auth()->id();
        }

        // Father and mother names are retired; accept only the sibling field.
        $input = $request->all();
        if (array_key_exists('sibling', $input)) {
            $family->sibling = $input['sibling'];
        }
        $family->save();
        return $this->success_message('Family Info has been updated successfully');
    }

    public function partner_expectation()
    {
        if (auth()->user()->partner_expectations) {
            return (new PartnerExpectationResource(auth()->user()->partner_expectations))->additional([
                'result' => true
            ]);
        } else {
            return $this->failure_message('No Data Found!!');
        }
    }

    public function partner_expectation_update(PartnerExpectationRequest $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'partner_country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'partner_state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
        ]);

        $partner = PartnerExpectation::where('user_id', auth()->id())->first();
        $oldPreferredCountry = $partner ? $partner->preferred_country_id : null;

        foreach ([
            ['min_height', 'max_height', 'height'],
            ['min_weight', 'max_weight', 'weight'],
        ] as [$minField, $maxField, $label]) {
            $minimum = array_key_exists($minField, $input)
                ? $input[$minField]
                : ($partner ? $partner->{$minField} : null);
            $maximum = array_key_exists($maxField, $input)
                ? $input[$maxField]
                : ($partner ? $partner->{$maxField} : null);

            if ($minimum !== null && $maximum !== null &&
                is_numeric($minimum) && is_numeric($maximum) &&
                (float) $minimum > (float) $maximum) {
                return response()->json([
                    'result' => false,
                    'message' => translate('The minimum partner ' . $label . ' cannot exceed the maximum.'),
                ], 422);
            }
        }

        $fields = [
            'general' => ['general'],
            'height' => ['partner_height'],
            'weight' => ['partner_weight'],
            'min_height' => ['min_height'],
            'max_height' => ['max_height'],
            'min_weight' => ['min_weight'],
            'max_weight' => ['max_weight'],
            'marital_status_id' => ['partner_marital_status'],
            'children_acceptable' => ['partner_children_acceptable'],
            'residence_country_id' => ['residence_country_id'],
            'religion_id' => ['partner_religion_id'],
            'caste_id' => ['partner_caste_id'],
            'sub_caste_id' => ['partner_sub_caste_id'],
            'education' => ['partner_education', 'pertner_education'],
            'profession' => ['partner_profession'],
            'smoking_acceptable' => ['smoking_acceptable'],
            'drinking_acceptable' => ['drinking_acceptable'],
            'diet' => ['partner_diet'],
            'body_type' => ['partner_body_type'],
            'personal_value' => ['partner_personal_value'],
            'manglik' => ['partner_manglik'],
            'language_id' => ['language_id'],
            'family_value_id' => ['family_value_id'],
            'preferred_country_id' => ['partner_country_id'],
            'complexion' => ['partner_complexion', 'pertner_complexion', 'complexion'],
            'eye_color' => ['partner_eye_color', 'eye_color'],
            'hair_color' => ['partner_hair_color', 'hair_color'],
        ];

        $hasUpdates = false;
        foreach ($fields as $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $input)) {
                    $hasUpdates = true;
                    break 2;
                }
            }
        }
        $stateWasProvided = array_key_exists('partner_state_id', $input);

        if (!$hasUpdates && !$stateWasProvided) {
            return response()->json([
                'result' => false,
                'message' => translate('No partner preferences were provided for update.'),
            ], 422);
        }

        $user = User::with('member')->findOrFail(auth()->id());
        $effectivePreferredCountry = array_key_exists('partner_country_id', $input)
            ? $input['partner_country_id']
            : ($partner ? $partner->preferred_country_id : null);
        $effectivePreferredState = $stateWasProvided
            ? ($input['partner_state_id'] ?: null)
            : ($partner ? $partner->preferred_state_id : null);

        if ($effectivePreferredState && !$effectivePreferredCountry) {
            return response()->json([
                'result' => false,
                'message' => translate('A country must be selected before assigning a preferred governorate.'),
            ], 422);
        }
        if ($effectivePreferredState && $effectivePreferredCountry &&
            !State::where('id', $effectivePreferredState)
                ->where('country_id', $effectivePreferredCountry)
                ->exists()) {
            return response()->json([
                'result' => false,
                'message' => translate('The preferred governorate does not belong to the preferred country.'),
            ], 422);
        }

        DB::transaction(function () use ($input, $fields, $stateWasProvided, $oldPreferredCountry) {
            $partner = PartnerExpectation::firstOrNew(['user_id' => auth()->id()]);

            foreach ($fields as $column => $keys) {
                foreach ($keys as $key) {
                    if (array_key_exists($key, $input)) {
                        $value = $input[$key];

                        if (in_array($column, ['height', 'min_height', 'max_height'], true) &&
                            $value !== null && is_numeric($value)) {
                            $value = (float) $value;
                            if ($value > 10) $value = $value / 100;
                        }
                        if ($column === 'children_acceptable' ||
                            $column === 'smoking_acceptable' ||
                            $column === 'drinking_acceptable') {
                            if ($value === 'dose_not_matter') $value = 'does_not_matter';
                        }

                        $partner->{$column} = $value;
                        break;
                    }
                }
            }

            if (array_key_exists('partner_country_id', $input)) {
                $newCountry = $input['partner_country_id'];
                if ((string) $newCountry !== (string) $oldPreferredCountry && !$stateWasProvided) {
                    // A governorate cannot remain linked to a different preferred country.
                    $partner->preferred_state_id = null;
                }
            }
            if ($stateWasProvided) {
                $partner->preferred_state_id = $input['partner_state_id'] ?: null;
            }

            $partner->save();
        });

        if (MemberUtility::can_auto_match($user->id)) {
            try {
                $profileMatchController = new ProfileMatchController();
                $profileMatchController->match_profiles($user->id);
            } catch (\Throwable $e) {
                \Log::warning('Partner preference matching refresh failed: ' . $e->getMessage());
            }
        }

        return $this->success_message('Partner Expectations Info has been updated successfully');
    }

    public function password_update(Request $request)
    {
        $this->validate($request, [
            'old_password'  => ['required'],
            'password'      => ['required', 'string', 'min:8', 'confirmed']
        ]);

        $user = User::findOrFail(auth()->id());

        if (Hash::check($request->old_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();
            return $this->success_message('Passwoed Updated successfully.');
        }

        return $this->failure_message('Old password do not matched.');
    }

    public function account_deactivation(Request $request)
    {
        $user = auth()->user();
        $user->deactivated = $request->deacticvation_status;
        $user->save();
        $msg = $request->deacticvation_status == 1 ? 'deactivated' : 'reactivated';
        return $this->success_message(translate('Your account ' . $msg . ' successfully!'));
    }

    public function public_profile($id)
    {
        // Load the profile aggregates used below in one predictable query.
        // The public endpoint must not assume every account has every relation.
        $user = User::with([
            'member',
            'education',
            'career',
            'physical_attributes',
            'hobbies',
            'attitude',
            'recidency',
            'spiritual_backgrounds',
            'lifestyles',
            'astrologies',
            'partner_expectations',
        ])->where('id', $id)->first();
        $auth_user = auth()->user();

        if (!$auth_user) {
            return response()->json([
                'result' => false,
                'message' => translate('Unauthenticated'),
            ], 401);
        }

        if (!$user) {
            return $this->failure_message('User Not Found');
        }

        // Accounts without a member row are not viewable as member profiles.
        $targetMember = $user->member;
        if (!$targetMember) {
            return response()->json([
                'result' => false,
                'message' => translate('Member profile is not available.'),
            ], 404);
        }

        $member_known_languages = null;
        $member_mother_tongue = null;
        $known_languages = json_decode($targetMember->known_languages ?? null);
        $mother_tongue = json_decode($targetMember->mothere_tongue ?? null);
            if ($known_languages != null) {
                $member_known_languages = LanguageResource::collection(MemberLanguage::whereIn('id', $known_languages)->get());
            }
            if ($mother_tongue != null) {
                $member_mother_tongue = new LanguageResource(MemberLanguage::where('id', $mother_tongue)->first());
            }
            $data['intoduction'] = new AboutUser($user);
            $data['basic_info'] = new PublicBasicInformation($user);

            $profile_pic_privacy = get_setting('profile_picture_privacy');
            $photo_view_request = ViewProfilePicture::where('user_id', $user->id)
                ->where('requested_by', $auth_user->id)->first();
            $viewerIsOwner = (int) $auth_user->id === (int) $user->id;
            $viewerHasPaidAccess = $viewerIsOwner || MemberUtility::is_female($auth_user)
                || SubscriptionUtility::hasActivePaidPackage($auth_user);
            $viewerMember = $auth_user->member;
            $profileImageQuota = (int) ($viewerMember->remaining_profile_image_view ?? 0);
            $canRequestProfileImage = $viewerIsOwner || MemberUtility::is_female($auth_user)
                || (SubscriptionUtility::hasActivePaidPackage($auth_user)
                    && ($profileImageQuota === -1 || $profileImageQuota > 0));
            $data['profile_pic_request'] = !$viewerIsOwner && $canRequestProfileImage
                && $user->photo != null && $user->photo_approved == 1
                && $profile_pic_privacy == 'only_me'
                && ($photo_view_request == null || (int) $photo_view_request->status === 0);

            $data['present_address'] = Address::where('user_id', $id)->where('type', 'present')->first() ? new AddressResource(Address::where('user_id', $id)->where('type', 'present')->first()) : null;
            // Contact information is private and is available only through the authenticated owner profile.
            $data['education'] = $user->education ? EducationResource::collection($user->education) : null;
            $data['career'] = $user->career ? CareerResource::collection($user->career) : null;
            $data['physical_attributes'] = $user->physical_attributes ? new PhysicalAttributes($user->physical_attributes) : null;
            $data['known_languages'] = $member_known_languages;
            $data['mother_tongue'] = $member_mother_tongue;
            $data['hobbies_interest'] = $user->hobbies ? new HobbiesInterests($user->hobbies) : null;
            $data['attitude_behavior'] = $user->attitude ? new AttitudesBehaviors($user->attitude) : null;
            $data['residence_info'] = $user->recidency ? new ResidenceInformation($user->recidency) : null;
            $data['spiritual_backgrounds'] = $user->spiritual_backgrounds ? new SpiritualSocialBackground($user->spiritual_backgrounds) : null;
            if ($user->lifestyles) {
                $publicLifestyle = (new LifeStyleResource($user->lifestyles))->toArray(request());
                unset($publicLifestyle['diet']);
                $data['lifestyles'] = $publicLifestyle;
            } else {
                $data['lifestyles'] = null;
            }
            $data['astrologies'] = $user->astrologies ? new AstronomicInformation($user->astrologies) : null;
            $data['permanent_address'] = Address::where('user_id', $id)->where('type', 'permanent')->first() ? new AddressResource(Address::where('user_id', $id)->where('type', 'permanent')->first()) : null;
            $data['partner_expectation'] = $user->partner_expectations ? new PartnerExpectationResource($user->partner_expectations) : null;

            $gallery_images = GalleryImage::where('user_id', $user->id)->get();
            $gallery_image_privacy = get_setting('gallery_image_privacy');
            $gallery_image_request = ViewGalleryImage::where('user_id', $user->id)->where('requested_by', $auth_user->id)->first();

            if ($gallery_image_privacy == 'only_me') {
                if ($viewerHasPaidAccess && ($viewerIsOwner || ($gallery_image_request !== null && (int) $gallery_image_request->status === 1))) {
                    $data['photo_gallery'] = GalleryImageResource::collection($gallery_images);
                } else {
                    $data['photo_gallery'] = RequestedGalleryImage::collection($gallery_images);
                }
            } elseif ($gallery_image_privacy == "all") {
                $data['photo_gallery'] = GalleryImageResource::collection($gallery_images);
            } else {
                if (MemberUtility::is_female($auth_user) || SubscriptionUtility::hasActivePaidPackage($auth_user)) {
                    $data['photo_gallery'] = GalleryImageResource::collection($gallery_images);
                } else {
                    $data['photo_gallery'] = RequestedGalleryImage::collection($gallery_images);
                }
            }

            $data['profile_match'] = null;
            $profile_match = ProfileMatch::where('user_id', $auth_user->id)
                ->where('match_id', $user->id)
                ->first();
            if (!empty($profile_match) && MemberUtility::can_auto_match($auth_user->id)) {
                $data['profile_match'] = $profile_match->match_percentage;
            }
            $data['view_contact_check'] = ViewContact::where('user_id', $user->id)->where('viewed_by', auth()->id())->first() ? true : false;

            if ($user->id != $auth_user->id) {
                $profileViewed = ProfileViewer::where('user_id', $user->id)->where('viewed_by', $auth_user->id)->first();
                if ($profileViewed == null) {
                    $viewerRecord = SubscriptionUtility::withQuota(
                        (int) $user->id,
                        'remaining_profile_viewer_view',
                        function ($owner, $ownerMember) use ($auth_user) {
                            if (ProfileViewer::where('user_id', $owner->id)
                                ->where('viewed_by', $auth_user->id)->exists()) {
                                return null;
                            }
                            return ProfileViewer::create([
                                'user_id' => $owner->id,
                                'viewed_by' => $auth_user->id,
                            ]);
                        }
                    );
                    if ($viewerRecord) {
                        try {
                            $notify_type = 'profile_viewed';
                            $id = unique_notify_id();
                            $notify_by = $auth_user->id;
                            $info_id = $user->id;
                            $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has viewed your profile.');
                            $route = route('member_profile', $auth_user->id);

                            if (get_setting('firebase_push_notification') == 1) {
                                $fcmTokens = User::where('id', $user->id)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                                self::sendFirebaseNotification($fcmTokens, $user, $notify_type, $message, $notify_by);
                            }

                            Notification::send($user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
                        } catch (\Exception $e) {
                            //
                        }
                    }
                }
            }

        return $this->response_data($data);
    }

    public static function sendFirebaseNotification($fcmTokens = null, $notify_user, $notify_type, $message, $notify_by = null)
    {
        if ($notify_user->fcm_token != null) {
            $data = (object)[];
            $data->fcm_token = $notify_user->fcm_token;
            $data->title = $notify_type;
            $data->text = $message;
            $data->notify_by = $notify_by;
            FirbaseNotification::send($data);
        }

        Larafirebase::withTitle(str_replace("_", " ", $notify_type))
            ->withBody($message)
            ->sendMessage($fcmTokens);
    }

    public function contact_info_update(Request $request)
    {
        $user = User::where('id', auth()->id())->first();
        $user->email = $request->email;
        $user->phone = $request->phone;
        if ($user->save()) {
            return $this->success_message('Contact Info has been updated successfully');
        } else {
            return $this->failure_message('Something went wrong');
        }
    }

    public function store_view_contact(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'exists:users,id']]);
        $viewer = auth()->user();
        if ((int) $request->id === (int) $viewer->id) {
            return $this->failure_message('You cannot request your own contact details.');
        }

        try {
            $created = SubscriptionUtility::withQuota(
                (int) $viewer->id,
                'remaining_contact_view',
                function ($lockedUser, $member) use ($request) {
                    if (ViewContact::where('user_id', $request->id)->where('viewed_by', $lockedUser->id)->exists()) {
                        return 'duplicate';
                    }
                    $contactRequest = new ViewContact();
                    $contactRequest->user_id = (int) $request->id;
                    $contactRequest->viewed_by = (int) $lockedUser->id;
                    $contactRequest->save();
                    return 'created';
                }
            );
        } catch (\Throwable $exception) {
            \Log::error('API contact-view request failed.', [
                'user_id' => $viewer->id,
                'target_user_id' => $request->id,
                'message' => $exception->getMessage(),
            ]);
            return $this->failure_message('Request failed to be sent!');
        }

        if ($created === 'created') {
            return $this->success_message('Request sent successfully!');
        }
        if ($created === 'duplicate') {
            return $this->failure_message('You already sent a request.');
        }
        return $this->failure_message('You do not have an active package or enough contact-view quota.');
    }

    /**
     * دالة جلب التطابقات المحدثة والجاهزة للتطابق التلقائي والشامل
     */
    public function matched_profile()
    {
        $user = auth()->user();
        $matched_profiles = collect();

        // 1. جلب قائمة الحسابات المجهولة أو المطرودة للمستخدم
        $ignored_to = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')->toArray();
        $ignored_by_ids = IgnoredUser::where('user_id', $user->id)->pluck('ignored_by')->toArray();

        // 2. المحاولة الأولى: جلب التطابقات المحسوبة مسبقاً من قاعدة البيانات
        if (MemberUtility::can_auto_match($user->id)) {
            $matchedQuery = ProfileMatch::orderBy('match_percentage', 'desc')
                ->where('user_id', $user->id)
                ->where('match_percentage', '>=', 50);

            if (count($ignored_to) > 0) {
                $matchedQuery = $matchedQuery->whereNotIn('match_id', $ignored_to);
            }
            if (count($ignored_by_ids) > 0) {
                $matchedQuery = $matchedQuery->whereNotIn('match_id', $ignored_by_ids);
            }

            $matched_profiles = $matchedQuery->limit(20)->get();
        }

        // 3. الخيار الاحتياطي الذكي: جلب جميع الأعضاء من الجنس الآخر عند عدم وجود سجلات سابقة
        if ($matched_profiles->isEmpty()) {
            $userGender = strtolower((string) ($user->member->gender ?? 'male'));
            
            // التثبت المرن من صيغ جنس الحساب الحالي
            $isMale = in_array($userGender, ['male', '1', 'm', 'ذكر']);

            $fallbackQuery = User::where('id', '!=', $user->id)
                ->where('blocked', 0)
                ->where('deactivated', 0)
                ->whereHas('member', function ($q) use ($isMale) {
                    if ($isMale) {
                        $q->whereIn(DB::raw('LOWER(gender)'), ['female', '2', 'f', 'أنثى']);
                    } else {
                        $q->whereIn(DB::raw('LOWER(gender)'), ['male', '1', 'm', 'ذكر']);
                    }
                });

            if (count($ignored_to) > 0) {
                $fallbackQuery = $fallbackQuery->whereNotIn('id', $ignored_to);
            }
            if (count($ignored_by_ids) > 0) {
                $fallbackQuery = $fallbackQuery->whereNotIn('id', $ignored_by_ids);
            }

            $fallbackUsers = $fallbackQuery->latest()->limit(20)->get();

            // خيار حماية أخير: إذا كانت قاعدة البيانات لا تحتوي على حسابات من الجنس الآخر، نجلب أحدث الأعضاء النشطين
            if ($fallbackUsers->isEmpty()) {
                $fallbackUsers = User::where('id', '!=', $user->id)
                    ->where('blocked', 0)
                    ->where('deactivated', 0)
                    ->latest()
                    ->limit(20)
                    ->get();
            }

            // تحويل نتائج الأعضاء وتضمين كافة علاقات الـ Model اللازمة لـ MatchedProfileResource
            $matched_profiles = $fallbackUsers->map(function ($u) use ($user) {
                $pm = new ProfileMatch();
                $pm->user_id = $user->id;
                $pm->match_id = $u->id;
                $pm->match_percentage = 85;
                
                // ربط كافة مسميات العلاقات المحتملة داخل Resource لتفادي إرجاع null
                $pm->setRelation('user', $u);
                $pm->setRelation('matchUser', $u);
                $pm->setRelation('match', $u);
                return $pm;
            });
        }

        return MatchedProfileResource::collection($matched_profiles);
    }

    public function account_delete(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $user->member ?  $user->member->delete() : '';
            Address::where('user_id', $user->id)->delete();
            Education::where('user_id', $user->id)->delete();
            Career::where('user_id', $user->id)->delete();
            PhysicalAttribute::where('user_id', $user->id)->delete();
            Hobby::where('user_id', $user->id)->delete();
            Attitude::where('user_id', $user->id)->delete();
            Recidency::where('user_id', $user->id)->delete();
            Lifestyle::where('user_id', $user->id)->delete();
            Astrology::where('user_id', $user->id)->delete();
            Family::where('user_id', $user->id)->delete();
            PartnerExpectation::where('user_id', $user->id)->delete();
            SpiritualBackground::where('user_id', $user->id)->delete();
            PackagePayment::where('user_id', $user->id)->delete();
            HappyStory::where('user_id', $user->id)->delete();
            Staff::where('user_id', $user->id)->delete();
            Shortlist::where('user_id', $user->id)->delete();
            IgnoredUser::where('user_id', $user->id)->delete();
            ReportedUser::where('user_id', $user->id)->delete();
            GalleryImage::where('user_id', $user->id)->delete();
            ExpressInterest::where('user_id', $user->id)->delete();
            ProfileMatch::where('user_id', $user->id)->delete();
            ChatThread::where('sender_user_id', auth()->user()->id)->orWhere('receiver_user_id', auth()->user()->id)->delete();
            User::destroy(auth()->user()->id);
            $user->tokens()
                ->where('id', $user->currentAccessToken()->id)
                ->delete();
            return $this->success_message('Your account has deleted successfully!');
        }
        return $this->failure_message('Something Went Wrong!');
    }
}