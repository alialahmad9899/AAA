<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationVerificationCode;
use App\Models\User;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Illuminate\Http\Request;

class MemberRegistrationVerificationController extends Controller
{
   
    public function sendRegVerificationCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:24|required_without:email',
            'country_code' => 'required_with:phone|string|max:8',
        ]);

        $email = !empty($validated['email']) ? strtolower(trim($validated['email'])) : null;
        $phone = !empty($validated['phone'])
            ? '+' . ltrim((string) $validated['country_code'], '+') . preg_replace('/\\D+/', '', $validated['phone'])
            : null;

        if ($email && User::where('email', $email)->exists()) {
            return response()->json(['status' => 0, 'message' => translate('Email already exists.')], 409);
        }
        if ($phone && User::where('phone', $phone)->exists()) {
            return response()->json(['status' => 0, 'message' => translate('Phone already exists.')], 409);
        }

        $verificationCode = (string) random_int(100000, 999999);
        $customerVerification = RegistrationVerificationCode::updateOrCreate(
            ['email' => $email, 'phone' => $phone],
            [
                'code' => $verificationCode,
                'is_verified' => 0,
                'expires_at' => now()->addMinutes(15),
            ]
        );

        $sent = false;
        try {
            if ($email) {
                EmailUtility::email_verification_for_registration_user(
                    'email_registration_verification',
                    $email,
                    $verificationCode
                );
                $sent = true;
            } elseif ($phone
                && addon_activation('otp_system')
                && get_sms_template('mobile_registration_verification', 'status') == 1) {
                SmsUtility::mobile_registration_verification($phone, $verificationCode);
                $sent = true;
            }
        } catch (\\Throwable $exception) {
            \\Log::warning('Registration verification code delivery failed.', [
                'channel' => $email ? 'email' : 'phone',
                'exception' => get_class($exception),
            ]);
        }

        if (!$sent) {
            $customerVerification->delete();

            return response()->json([
                'status' => 0,
                'message' => translate('Verification code sending failed.'),
            ], 503);
        }

        return response()->json([
            'status' => 1,
            'message' => translate('Verification code sent successfully.'),
        ]);
    }

    public function regVerifyCodeConfirmation(Request $request)
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:24|required_without:email',
            'code' => ['required', 'string', 'regex:/^\\d{6}$/'],
        ]);

        $query = RegistrationVerificationCode::where('code', $validated['code'])
            ->where('expires_at', '>', now());

        if (!empty($validated['email'])) {
            $query->where('email', strtolower(trim($validated['email'])));
        } else {
            $phone = preg_replace('/\\D+/', '', (string) $validated['phone']);
            $query->where('phone', 'like', '%' . $phone);
        }

        $customerVerification = $query->first();
        if (!$customerVerification) {
            return response()->json([
                'status' => 0,
                'message' => translate('Verification Code did not match or has expired.'),
            ], 422);
        }

        $customerVerification->is_verified = 1;
        $customerVerification->save();

        return response()->json([
            'status' => 1,
            'message' => translate('Verification Successful'),
        ]);
    }

}
