<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\OTPVerificationController;
use App\Http\Requests\AuthRequest;
use App\Http\Resources\Profile\MaritialStatusResource;
use App\Models\EmailTemplate;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Upload;
use App\Models\User;
use App\Notifications\AppEmailVerificationNotification;
use App\Notifications\DbStoreNotification;
use App\Notifications\VerificationCode;
use App\Services\MemberService;
use App\Services\UserService;
use App\Utility\EmailUtility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Socialite;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Rules\RecaptchaRule;

class AuthController extends Controller
{
    /**
     * Registration using api
     */
    public function signup(AuthRequest $request)
    {
        $package = Package::where('id', 1)->first();
        if (!$package) {
            return response()->json([
                'result' => false,
                'message' => translate('Registration package is not available. Please contact support.'),
            ], 503);
        }

        // إنشاء المستخدم والعضوية والصورة ضمن معاملة واحدة حتى لا يبقى حساب يتيم.
        $storedPhotoPath = null;

        try {
            [$user, $member] = DB::transaction(function () use ($request, $package, &$storedPhotoPath) {
                $user_service = new UserService();
                $user = $user_service->store($request->safe()->except([
                    'gender',
                    'birthday',
                    'on_behalves_id',
                    'date_of_birth',
                    'on_behalf',
                    'photo',
                    'avatar',
                    'image',
                ]));

                $request->merge(['user_id' => $user->id]);
                $member_service = new MemberService();
                $member = $member_service->store(
                    $request->only(['gender', 'birthday', 'on_behalves_id', 'user_id']),
                    $package
                );

                if (!$member) {
                    throw new \RuntimeException('Unable to create member registration record.');
                }

                // يدعم API الصورة أثناء إنشاء الحساب إذا أرسلها العميل مباشرة.
                $photoFile = $request->file('photo') ?? $request->file('avatar') ?? $request->file('image');

                if ($photoFile) {
                    if (!$photoFile->isValid()) {
                        throw new \RuntimeException('The uploaded profile photo is invalid.');
                    }

                    $extension = strtolower($photoFile->extension() ?: $photoFile->getClientOriginalExtension());
                    $filename = (string) Str::uuid() . '.' . $extension;
                    $path = $photoFile->storeAs('uploads/all', $filename, 'public');

                    if (!$path) {
                        throw new \RuntimeException('Unable to store the profile photo.');
                    }

                    $storedPhotoPath = $path;

                    $upload = new Upload();
                    $upload->file_original_name = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $upload->file_name = $path;
                    $upload->user_id = $user->id;
                    $upload->extension = $extension;
                    $upload->type = 'image';
                    $upload->file_size = $photoFile->getSize();

                    if (!$upload->save()) {
                        throw new \RuntimeException('Unable to save the profile photo record.');
                    }

                    $user->avatar_original = $upload->id;
                    $user->avatar = $path;

                    if (\Schema::hasColumn('users', 'photo')) {
                        $user->photo = $upload->id;
                    }

                    $approvalRequired = (int) (\App\Models\Setting::where('type', 'profile_picture_approval_by_admin')->value('value') ?? 0) === 1;
                    $user->photo_approved = $approvalRequired ? 0 : 1;

                    if (!$user->save()) {
                        throw new \RuntimeException('Unable to link the profile photo to the account.');
                    }
                }

                return [$user, $member];
            });
        } catch (\Throwable $e) {
            if ($storedPhotoPath) {
                Storage::disk('public')->delete($storedPhotoPath);
            }

            \Log::error('API signup transaction failed: ' . $e->getMessage());

            return response()->json([
                'result' => false,
                'message' => translate('Unable to complete registration. Please try again.'),
            ], 500);
        }

        // تحديث FCM Token إن وجد أثناء التسجيل
        $fcmToken = $request->fcm_token ?? $request->device_token;
        if ($fcmToken) {
            $user->fcm_token = $fcmToken;
            $user->save();
        }

        if (addon_activation('otp_system') && $request->phone != null && get_setting('registration_verification') != 1) {
            $otpController = new OTPVerificationController();
            $otpController->send_code($user);
        }

        // Email to member
        if ($request->email != null && env('MAIL_USERNAME') != null) {
            $account_oppening_email = EmailTemplate::where('identifier', 'account_oppening_email')->first();
            if ($account_oppening_email && $account_oppening_email->status == 1) {
                try {
                    EmailUtility::account_oppening_email($user->id, $request->password);
                } catch (\Exception $e) {
                }
            }
        }

        try {
            $notify_type = 'member_registration';
            $id = unique_notify_id();
            $notify_by = $user->id;
            $info_id = $user->id;
            $message = translate('A new member has been registered to your system. Name: ') . $user->first_name . ' ' . $user->last_name;
            $route = route('members.index', $user->membership);

            Notification::send(User::where('user_type', 'admin')->first(), new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
        } catch (\Exception $e) {
            // dd($e);
        }

        if (env('MAIL_USERNAME') != null && get_email_template('account_opening_email_to_admin', 'status') == 1) {
            $admin = User::where('user_type', 'admin')->first();
            EmailUtility::account_opening_email_to_admin($user, $admin);
        }
        if ((get_setting('email_verification') != 1 && !addon_activation('otp_system')) || get_setting('registration_verification') === '1') {
            if ($user->email != null || $user->phone != null) {
                $user->email_verified_at = date('Y-m-d H:i:s');
                $user->save();
            }
        } else {
            try {
                // verification code send to user
                $user->notify(new VerificationCode($user));
            } catch (\Exception $e) {
            }
        }

        return $this->authResponse($user);
    }

    /**
     * Login using api
     */
    public function signin(Request $request)
    {
        $user = User::where('email', $request->email_or_phone)
            ->orWhere('phone', $request->email_or_phone)
            ->first();

        if ($request->has('identity_matrix') && !empty($request->identity_matrix)) {
            if (\App\Utility\MemberUtility::member_check($request->identity_matrix) == false) {
                return response()->json(['result' => false, 'message' => 'Identity matrix error', 'user' => null], 401);
            }
        }

        if ($user != null) {
            if (Hash::check($request->password, $user->password)) {
                $fcmToken = $request->fcm_token ?? $request->device_token;
                if ($fcmToken) {
                    $user->fcm_token = $fcmToken;
                    $user->save();
                }

                return $this->authResponse($user);
            }
            return response()->json(['result' => false, 'message' => translate('Unauthorized'), 'user' => null], 401);
        }
        return response()->json(['result' => false, 'message' => translate('User not found'), 'user' => null], 401);
    }

    /**
     * Google Sign-In Direct API Endpoint
     */
    public function googleSignIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'nullable|string',
            'google_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors()->first(),
                'user' => null,
            ], 422);
        }

        $email = strtolower(trim($request->email));
        $googleId = $request->google_id;

        $user = User::where('email', $email)
            ->orWhere('provider_id', $googleId)
            ->first();

        if ($user) {
            if (empty($user->provider_id)) {
                $user->provider_id = $googleId;
            }
            if (empty($user->email_verified_at)) {
                $user->email_verified_at = date('Y-m-d H:i:s');
            }

            $fcmToken = $request->fcm_token ?? $request->device_token;
            if ($fcmToken) {
                $user->fcm_token = $fcmToken;
            }
            $user->save();

            if ($user->approved == 0) {
                return response()->json([
                    'result' => false,
                    'message' => translate('Please wait for admin approval'),
                    'user' => null,
                ], 401);
            }

            return $this->authResponse($user);
        } else {
            return response()->json([
                'result' => false,
                'is_registered' => false,
                'message' => translate('User not found. Please complete registration.'),
                'google_data' => [
                    'email' => $email,
                    'name' => $request->name ?? '',
                    'google_id' => $googleId,
                ]
            ], 200);
        }
    }

    /**
     * Social Login
     */
    public function socialLogin(Request $request)
    {
        if (!$request->provider) {
            return response()->json([
                'result' => false,
                'message' => translate('User not found'),
                'user' => null,
            ]);
        }

        switch ($request->social_provider) {
            case 'facebook':
                $social_user = Socialite::driver('facebook')->fields(['name', 'first_name', 'last_name', 'email']);
                break;
            case 'google':
                $social_user = Socialite::driver('google')->scopes(['profile', 'email']);
                break;
            case 'twitter':
                $social_user = Socialite::driver('twitter');
                break;
            case 'apple':
                $social_user = Socialite::driver('sign-in-with-apple')->scopes(['name', 'email']);
                break;
            default:
                $social_user = null;
        }
        if ($social_user == null) {
            return response()->json(['result' => false, 'message' => translate('No social provider matches'), 'user' => null]);
        }

        $existingUserByProviderId = User::where('provider_id', $request->provider)->first();

        if ($existingUserByProviderId) {
            if ($existingUserByProviderId->approved == 0) {
                return response()->json(['result' => false, 'message' => translate('Please wait for admin approval'), 'user' => null], 401);
            } else {
                $fcmToken = $request->fcm_token ?? $request->device_token;
                if ($fcmToken) {
                    $existingUserByProviderId->fcm_token = $fcmToken;
                    $existingUserByProviderId->save();
                }
                return $this->authResponse($existingUserByProviderId);
            }
        } else {
            $newUser                     = new User;
            $newUser->first_name         = $request->name;
            $newUser->email              = $request->email;
            $newUser->email_verified_at  = date('Y-m-d H:i:s');
            $newUser->provider_id        = $request->provider;
            $newUser->code               = unique_code();
            $newUser->membership         = 1;
            $newUser->approved           = get_setting('member_verification') == 1 ? 0 : 1;
            
            $fcmToken = $request->fcm_token ?? $request->device_token;
            if ($fcmToken) {
                $newUser->fcm_token = $fcmToken;
            }
            
            $newUser->save();

            $member                             = new Member;
            $member->user_id                    = $newUser->id;
            $member->gender                     = null;
            $member->on_behalves_id             = null;
            $member->birthday                   = null;

            $package                            = Package::where('id', 1)->first();
            $member->current_package_id         = $package->id;
            $member->remaining_interest         = $package->express_interest;
            $member->remaining_photo_gallery    = $package->photo_gallery;
            $member->remaining_contact_view     = $package->contact;
            $member->remaining_profile_viewer_view  = $package->profile_viewers_view;
            $member->remaining_profile_image_view   = $package->profile_image_view;
            $member->remaining_gallery_image_view   = $package->gallery_image_view;
            $member->auto_profile_match         = $package->auto_profile_match;
            $member->package_validity           = Date('Y-m-d', strtotime($package->validity . " days"));
            $member->save();

            if ($newUser->approved == 0) {
                return response()->json(['result' => false, 'message' => translate('Please wait for admin approval'), 'user' => null], 401);
            } else {
                return $this->authResponse($newUser);
            }
        }
    }

    /**
     * Log Out using api
     */
    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $user
                ->tokens()
                ->where('id', $user->currentAccessToken()->id)
                ->delete();
        }
        return $this->success_message('Successfully logged out');
    }

    /**
     * Log in success
     */
    protected function authResponse($user)
    {
        $maritial_status = MaritalStatus::where('id', $user->member->marital_status_id ?? 0)->first();
        $token = $user->createToken('API Token')->plainTextToken;
        $age = Carbon::parse($user->member->birthday ?? now())->age;

        return response()->json([
            'result' => true,
            'message' => translate('Successfully logged in'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => null,
            'user' => [
                'id' => $user->id,
                'type' => $user->user_type,
                'name' => $user->first_name . ' ' . $user->last_name,
                'membership' => $user->membership,
                'email_verified_at' => $user->email_verified_at,
                'photo_approved' => $user->photo_approved,
                'blocked' => $user->blocked,
                'deactivated' => $user->deactivated,
                'approved' => $user->approved,
                'email' => $user->email,
                'birthday' => $age,
                'height' => $user->physical_attributes ? $user->physical_attributes->height : 0,
                'marital_status_id' => $maritial_status ? new MaritialStatusResource($maritial_status) :  new MaritialStatusResource($maritial_status),
                'avatar' => $user->avatar ?? '',
                'avatar_original' => uploaded_asset($user->avatar_original) ?? '',
                'phone' => $user->phone ?? '',
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        if ($request->send_code_by == 'email') {
            $this->validate($request, [
                'email_or_phone' => 'required|email',
            ]);
            $user = User::where('email', $request->email_or_phone)->first();
        } else {
            $this->validate($request, [
                'email_or_phone' => 'required',
            ]);
            $user = User::where('phone', $request->email_or_phone)->first();
        }
        if ($user) {
            $user->verification_code = rand(100000, 999999);
            $user->save();
            if ($request->send_code_by == 'phone') {
                $otpController = new OTPVerificationController();
                $otpController->send_code($user);
            } else {
                try {
                    $user->notify(new AppEmailVerificationNotification($user));
                } catch (\Exception $e) {
                    return response()->json(
                        [
                            'result' => false,
                            'message' => translate('SMTP not configured properly. Please contact with admin'),
                        ],
                        404,
                    );
                }
            }
        } else {
            return response()->json(
                [
                    'result' => false,
                    'message' => translate('User is not found'),
                ],
                404,
            );
        }

        return response()->json(
            [
                'result' => true,
                'message' => translate('A code is sent'),
            ],
            200,
        );
    }

    public function verifyCode(Request $request)
    {
        $user = auth()->user();

        $this->validate($request, [
            'verification_code' => 'required',
        ]);
        if ($user && $user->verification_code == $request->verification_code) {
            $user->email_verified_at = Carbon::now();
            $user->verification_code = null;

            $user->save();
            return $this->success_message('Your account is now verified');
        }
        return $this->failure_message('Verification code does not match!!');
    }

    public function resendVerifyCode(Request $request)
    {
        $user = auth()->user();
        $user->verification_code = rand(1000, 999999);
        $user->save();
        try {
            $user->notify(new VerificationCode($user));
        } catch (\Exception $e) {
        }
        return response()->json(
            [
                'result' => true,
                'message' => 'OTP resend successfull.',

            ],
            200
        );
    }

    public function resetPassword(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($request->send_code_by == 'phone' && !empty($request->email_or_phone)) {
            $user = User::where('phone', $request->email_or_phone)->first();
        } elseif ($request->send_code_by == 'email' && !empty($request->email_or_phone)) {
            $user = User::where('email', $request->email_or_phone)->first();
        }

        if (!$user) {
            return $this->failure_message('User not found!!');
        }
        if ($user->verification_code == $request->verification_code) {
            $user->password = Hash::make($request['password']);
            $user->verification_code = null;
            $user->save();

            return $this->success_message('Password has been updated, you can login now');
        }
        return $this->failure_message('Verification code does not match.');
    }

    public function authData($user)
    {
        $maritial_status = MaritalStatus::where('id', $user->member->marital_status_id ?? 0)->first();
        $age = Carbon::parse($user->member->birthday ?? now())->age;
        return response()->json(
            [
                'id' => $user->id,
                'type' => $user->user_type,
                'name' => $user->first_name . ' ' . $user->last_name,
                'membership' => $user->membership,
                'email_verified_at' => $user->email_verified_at,
                'photo_approved' => $user->photo_approved,
                'blocked' => $user->blocked,
                'deactivated' => $user->deactivated,
                'approved' => $user->approved,
                'email' => $user->email,
                'birthday' => $age,
                'height' => $user->physical_attributes ? $user->physical_attributes->height : 0,
                'marital_status_id' => $maritial_status ? new MaritialStatusResource($maritial_status) :  new MaritialStatusResource($maritial_status),
                'avatar' => $user->avatar ?? '',
                'avatar_original' => uploaded_asset($user->avatar_original) ?? '',
                'phone' => $user->phone ?? '',
            ]
        );
    }

    public function checkedData()
    {
        $user = auth()->user();
        return response()->json(
            [
                'id' => $user->id,
                'type' => $user->user_type,
                'name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone ?? ''
            ]
        );
    }

    public function getUserByToken()
    {
        $token = PersonalAccessToken::findToken(request()->bearerToken());
        $user = null;
        if ($token) {
            $user = $token->tokenable;
            return $this->authData($user);
        }
        return response()->json(
            ['user' => $user]
        );
    }

    public function update_device_token(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                $user = $token->tokenable;
            }
        }

        if (!$user) {
            return response()->json([
                'result' => false,
                'message' => translate("User not found.")
            ], 401);
        }

        $fcmToken = $request->fcm_token ?? $request->device_token;

        if ($fcmToken) {
            $user->fcm_token = $fcmToken;
            $user->save();

            return response()->json([
                'result' => true,
                'message' => translate("device token updated")
            ]);
        }

        return response()->json([
            'result' => false,
            'message' => translate("Device token is required")
        ], 400);
    }
}