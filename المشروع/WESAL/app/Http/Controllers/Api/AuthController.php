<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\AuthRequest;
use App\Security\GoogleIdTokenVerifier;
use App\Security\PasswordResetCode;
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
use App\Utility\SmsUtility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        // Create user, member, profile image and verification state atomically.
        $storedPhotoPath = null;

        try {
            // The mobile API does not require email verification. Optional phone OTP
            // remains governed by its separate OTP add-on flow.
            $autoVerify = true;

            $user = DB::transaction(function () use ($request, &$storedPhotoPath, $autoVerify) {
                $package = Package::where('id', 1)->first();
                if (!$package) {
                    throw new \RuntimeException('Default membership package is missing.');
                }

                $user_service = new UserService();
                $userData = $request->safe()->except([
                    'gender', 'birthday', 'on_behalves_id', 'date_of_birth',
                    'on_behalf', 'photo', 'avatar', 'image',
                ]);
                // AuthRequest prepares new app accounts with the free membership tier.
                // safe() omits this server-prepared value because it is not a client input rule.
                $userData['membership'] = 1;
                $user = $user_service->store($userData);
                $member_service = new MemberService();
                // Normalize the field names sent by the Flutter signup form.
                // The app sends date_of_birth and on_behalf; MemberService expects
                // birthday and on_behalves_id.
                $memberData = [
                    'gender' => $request->input('gender'),
                    'birthday' => $request->input('date_of_birth') ?? $request->input('birthday'),
                    'on_behalves_id' => $request->input('on_behalf') ?? $request->input('on_behalves_id'),
                    'user_id' => $user->id,
                ];
                $member_service->store($memberData, $package);

                $photoFile = $request->file('photo')
                    ?? $request->file('avatar')
                    ?? $request->file('image');

                if ($photoFile) {
                    if (!$photoFile->isValid()) {
                        throw new \RuntimeException('Invalid profile image upload.');
                    }

                    $storedPhotoPath = $photoFile->store('uploads/all');
                    if (!is_string($storedPhotoPath) || $storedPhotoPath === '') {
                        throw new \RuntimeException('Unable to store profile image.');
                    }

                    $upload = new Upload();
                    $upload->file_original_name = $photoFile->getClientOriginalName();
                    $upload->file_name = $storedPhotoPath;
                    $upload->user_id = $user->id;
                    $upload->extension = $photoFile->extension()
                        ?: strtolower($photoFile->getClientOriginalExtension());
                    $upload->type = 'image';
                    $upload->file_size = $photoFile->getSize();
                    $upload->save();

                    // Some deployed databases use "photo" as the canonical upload ID and do not have avatar_original.
                    if (\Schema::hasColumn('users', 'avatar_original')) {
                        $user->avatar_original = $upload->id;
                    }
                    // The deployed users table may not include the legacy avatar path column.
                    if (\Schema::hasColumn('users', 'avatar')) {
                        $user->avatar = $storedPhotoPath;
                    }
                    if (\Schema::hasColumn('users', 'photo')) {
                        $user->photo = $upload->id;
                    }
                    $user->photo_approved =
                        get_setting('profile_picture_approval_by_admin') == 1 ? 0 : 1;
                    $user->save();
                }

                if ($autoVerify && $user->email != null) {
                    $user->email_verified_at = date('Y-m-d H:i:s');
                    $user->save();
                }

                $fcmToken = $request->fcm_token ?? $request->device_token;
                if ($fcmToken) {
                    $user->fcm_token = $fcmToken;
                    $user->save();
                }

                return $user;
            });
        } catch (\Throwable $e) {
            if ($storedPhotoPath) {
                try {
                    Storage::disk(config('filesystems.default'))->delete($storedPhotoPath);
                } catch (\Throwable $cleanupError) {
                    \Log::warning('Signup image cleanup failed: ' . $cleanupError->getMessage());
                }
            }

            \Log::error('API signup transaction failed: ' . $e->getMessage());

            return response()->json([
                'result' => false,
                'message' => translate('Unable to complete registration. Please try again.'),
            ], 500);
        }

        // External notifications and emails are best-effort after the transaction.
        if (addon_activation('otp_system') && $request->phone != null && get_setting('registration_verification') != 1) {
            try {
                (new OTPVerificationController())->send_code($user);
            } catch (\Throwable $e) {
                \Log::warning('Signup OTP notification failed: ' . $e->getMessage());
            }
        }

        if ($request->email != null && env('MAIL_USERNAME') != null) {
            try {
                $welcomeTemplate = EmailTemplate::where('identifier', 'account_oppening_email')->first();
                if ($welcomeTemplate && $welcomeTemplate->status == 1) {
                    EmailUtility::account_oppening_email($user->id, $request->password);
                }
            } catch (\Throwable $e) {
                \Log::warning('Signup welcome email failed: ' . $e->getMessage());
            }
        }

        try {
            $admin = User::where('user_type', 'admin')->first();
            if ($admin) {
                $notify_type = 'member_registration';
                $id = unique_notify_id();
                $notify_by = $user->id;
                $info_id = $user->id;
                $message = translate('A new member has been registered to your system. Name: ') . $user->first_name . ' ' . $user->last_name;
                $membershipId = (int) ($user->membership ?: 1);
                $route = route('members.index', ['id' => $membershipId]);

                Notification::send(
                    $admin,
                    new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route)
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Signup admin notification failed: ' . $e->getMessage());
        }

        try {
            if (env('MAIL_USERNAME') != null && get_email_template('account_opening_email_to_admin', 'status') == 1) {
                $admin = User::where('user_type', 'admin')->first();
                if ($admin) {
                    EmailUtility::account_opening_email_to_admin($user, $admin);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Signup admin email failed: ' . $e->getMessage());
        }

        return $this->authResponse($user);
    }

        /**
     * Login using api
     */
    public function signin(Request $request)
    {
        $validated = $request->validate([
            'email_or_phone' => 'required|string|max:255',
            'password' => 'required|string|max:4096',
        ]);

        $identity = trim($validated['email_or_phone']);
        $user = User::where('email', $identity)
            ->orWhere('phone', $identity)
            ->first();

        if ($request->filled('identity_matrix')
            && \App\Utility\MemberUtility::member_check($request->input('identity_matrix')) === false) {
            return response()->json([
                'result' => false,
                'message' => translate('Unauthorized'),
                'user' => null,
            ], 401);
        }

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'result' => false,
                'message' => translate('Unauthorized'),
                'user' => null,
            ], 401);
        }

        $fcmToken = $request->input('fcm_token') ?? $request->input('device_token');
        if (is_string($fcmToken) && $fcmToken !== '') {
            $user->fcm_token = $fcmToken;
            $user->save();
        }

        return $this->authResponse($user);
    }

    /**
     * Google Sign-In: the ID token is verified server-side before account lookup.
     */
    /**
     * Google Sign-In Direct API Endpoint
     */
    public function googleSignIn(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string|max:8192',
        ]);

        $claims = app(GoogleIdTokenVerifier::class)->verify($validated['id_token']);
        if (!$claims) {
            return response()->json([
                'result' => false,
                'message' => translate('Google sign-in could not be verified. Please try again.'),
                'user' => null,
            ], 401);
        }

        // Never trust email or google_id sent separately by the mobile client.
        $email = strtolower(trim((string) $claims['email']));
        $googleId = (string) $claims['sub'];

        $user = User::where('provider_id', $googleId)->first();
        if (!$user) {
            $userByEmail = User::where('email', $email)->first();

            if ($userByEmail && !empty($userByEmail->provider_id)
                && !hash_equals((string) $userByEmail->provider_id, $googleId)) {
                return response()->json([
                    'result' => false,
                    'message' => translate('This account is linked to another Google identity. Sign in using the original method.'),
                    'user' => null,
                ], 409);
            }

            // Google only guarantees ongoing control of the email for Gmail and
            // hosted Workspace accounts. External addresses must not be silently
            // linked to an existing local account using an old email claim.
            $domain = substr(strrchr($email, '@') ?: '', 1);
            $googleHostedDomain = $claims['hd'] ?? null;
            $googleControlsEmail = strcasecmp($domain, 'gmail.com') === 0
                || (is_string($googleHostedDomain) && $googleHostedDomain !== '');

            if ($userByEmail && !$googleControlsEmail) {
                return response()->json([
                    'result' => false,
                    'message' => translate('Sign in with your password before linking this Google account.'),
                    'user' => null,
                ], 409);
            }

            $user = $userByEmail;
        }

        if ($user) {
            if (empty($user->provider_id)) {
                $user->provider_id = $googleId;
            }

            if (empty($user->email_verified_at)) {
                $user->email_verified_at = Carbon::now();
            }

            $fcmToken = $request->input('fcm_token') ?? $request->input('device_token');
            if (is_string($fcmToken) && $fcmToken !== '') {
                $user->fcm_token = $fcmToken;
            }
            $user->save();

            return $this->authResponse($user);
        }

        return response()->json([
            'result' => false,
            'is_registered' => false,
            'message' => translate('User not found. Please complete registration.'),
            'google_data' => [
                'email' => $email,
                'name' => is_string($claims['name'] ?? null) ? $claims['name'] : '',
                'google_id' => $googleId,
            ],
        ], 200);
    }

    /**
     * Legacy provider login accepted client-supplied identity fields without
     * provider token verification. Use the verified /auth/google endpoint.
     */
    public function socialLogin(Request $request)
    {
        return response()->json([
            'result' => false,
            'message' => translate('This legacy social sign-in endpoint is disabled. Use verified sign-in.'),
        ], 410);
    }

    /**
     * Log Out using api
     */
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
        $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 43200));
        $token = $user->createToken('API Token', ['*'], $expiresAt)->plainTextToken;
        $age = Carbon::parse($user->member->birthday ?? now())->age;
        $photoUrl = uploaded_asset($user->photo) ?: uploaded_asset($user->avatar_original ?? null);

        return response()->json([
            'result' => true,
            'message' => translate('Successfully logged in'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'type' => $user->user_type,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'membership' => $user->membership,
                'email_verified_at' => $user->email_verified_at,
                'photo_approved' => $user->photo_approved,
                'blocked' => $user->blocked,
                'deactivated' => $user->deactivated,
                'approved' => $user->approved,
                'email' => $user->email,
                'birthday' => $age,
                'height' => $user->physical_attributes ? $user->physical_attributes->height : 0,
                'marital_status_id' => $maritial_status ? new MaritialStatusResource($maritial_status) : new MaritialStatusResource($maritial_status),
                'avatar' => $user->avatar ?? '',
                'photo' => $photoUrl ?? '',
                'avatar_original' => $photoUrl ?? '',
                'phone' => $user->phone ?? '',
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'send_code_by' => 'required|in:email,phone',
            'email_or_phone' => 'required|string|max:255',
        ]);

        $identity = trim($validated['email_or_phone']);
        $user = $validated['send_code_by'] === 'email'
            ? User::where('email', strtolower($identity))->first()
            : User::where('phone', $identity)->first();

        // The same response is returned whether or not the account exists.
        if ($user) {
            $issuedCode = PasswordResetCode::issue(10);
            $user->password_reset_code_hash = $issuedCode['hash'];
            $user->password_reset_code_expires_at = $issuedCode['expires_at'];

            // Keep the code only in memory for the existing notification templates.
            $user->verification_code = $issuedCode['code'];
            $user->save();

            try {
                if ($validated['send_code_by'] === 'phone') {
                    SmsUtility::password_reset($user, $issuedCode['code']);
                } else {
                    $user->notify(new AppEmailVerificationNotification($user));
                }
            } catch (\Throwable $exception) {
                \Log::warning('API password reset delivery failed.', [
                    'user_id' => $user->id,
                    'channel' => $validated['send_code_by'],
                    'exception' => get_class($exception),
                ]);

                $user->password_reset_code_hash = null;
                $user->password_reset_code_expires_at = null;
            }

            // Never leave the raw recovery code in the database after delivery.
            $user->verification_code = null;
            $user->save();
        }

        return response()->json([
            'result' => true,
            'message' => translate('If the account exists, a password reset code has been sent.'),
        ], 200);
    }


    public function verifyCode(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'verification_code' => 'required|string|regex:/^\d{4,6}$/',
        ]);

        $storedCode = $user ? (string) $user->verification_code : '';
        if ($user && $storedCode !== '' && hash_equals($storedCode, $validated['verification_code'])) {
            $user->email_verified_at = Carbon::now();
            $user->verification_code = null;
            $user->save();

            return $this->success_message('Your account is now verified');
        }

        return $this->failure_message('Verification code does not match!!');
    }

    public function resendVerifyCode(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['result' => false, 'message' => translate('Unauthenticated.')], 401);
        }

        $user->verification_code = (string) random_int(1000, 999999);
        $user->save();

        try {
            $user->notify(new VerificationCode($user));
        } catch (\Throwable $exception) {
            \Log::warning('Verification code resend failed.', [
                'user_id' => $user->id,
                'exception' => get_class($exception),
            ]);
            return response()->json([
                'result' => false,
                'message' => translate('Unable to send the verification code. Please try again later.'),
            ], 503);
        }

        return response()->json([
            'result' => true,
            'message' => 'OTP resend successfull.',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'send_code_by' => 'required|in:email,phone',
            'email_or_phone' => 'required|string|max:255',
            'verification_code' => ['required', 'string', 'regex:/^\d{4,6}$/'],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $identity = trim($validated['email_or_phone']);
        $user = $validated['send_code_by'] === 'phone'
            ? User::where('phone', $identity)->first()
            : User::where('email', $identity)->first();

        if (!$user || !PasswordResetCode::isValid(
            $validated['verification_code'],
            $user->password_reset_code_hash,
            $user->password_reset_code_expires_at
        )) {
            return response()->json([
                'result' => false,
                'message' => translate('The reset code is invalid or expired. Request a new code.'),
            ], 422);
        }

        $updated = DB::transaction(function () use ($user, $validated) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
            if (!$lockedUser || !PasswordResetCode::isValid(
                $validated['verification_code'],
                $lockedUser->password_reset_code_hash,
                $lockedUser->password_reset_code_expires_at
            )) {
                return false;
            }

            $lockedUser->password = Hash::make($validated['password']);
            $lockedUser->verification_code = null;
            $lockedUser->password_reset_code_hash = null;
            $lockedUser->password_reset_code_expires_at = null;
            $lockedUser->setRememberToken(Str::random(60));
            $lockedUser->save();

            // A password reset invalidates every previously issued mobile token.
            $lockedUser->tokens()->delete();

            return true;
        });

        if (!$updated) {
            return response()->json([
                'result' => false,
                'message' => translate('The reset code is invalid or expired. Request a new code.'),
            ], 422);
        }

        return $this->success_message('Password has been updated, you can login now');
    }

    public function authData($user)
    {
        $maritial_status = MaritalStatus::where('id', $user->member->marital_status_id ?? 0)->first();
        $age = Carbon::parse($user->member->birthday ?? now())->age;
        $photoUrl = uploaded_asset($user->photo) ?: uploaded_asset($user->avatar_original ?? null);

        return response()->json(
            [
                'id' => $user->id,
                'type' => $user->user_type,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
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
                'photo' => $photoUrl ?? '',
                'avatar_original' => $photoUrl ?? '',
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

    public function getUserByToken(Request $request)
    {
        return $this->authData($request->user());
    }

    public function update_device_token(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'result' => false,
                'message' => translate('Unauthenticated.'),
            ], 401);
        }

        $fcmToken = $request->input('fcm_token') ?? $request->input('device_token');
        if (!is_string($fcmToken) || trim($fcmToken) === '' || strlen($fcmToken) > 4096) {
            return response()->json([
                'result' => false,
                'message' => translate('A valid device token is required.'),
            ], 422);
        }

        $user->fcm_token = $fcmToken;
        $user->save();

        return response()->json([
            'result' => true,
            'message' => translate('device token updated'),
        ]);
    }

}