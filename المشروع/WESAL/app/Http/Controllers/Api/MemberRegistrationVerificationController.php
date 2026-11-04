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

        $email = $request->email ?? null;
        $phone = $request->phone != null ? '+' . $request->country_code . $request->phone : null;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (User::where('email', $email)->first() != null) {
                return response()->json(['status' => 0, 'message' => translate('Email already exists.')]);
            }
        } elseif (User::where('phone', $phone)->first() != null) {
            return response()->json(['status' => 0, 'message' => translate('Phone already exists.')]);
        }

        $verificationCode = rand(100000, 999999);
        $customerVerification = RegistrationVerificationCode::updateOrCreate(
            ['email' => $email, 'phone' => $phone],
            ['code' => $verificationCode]
        );
        $success = 1;

        if ($email) {
            try {
                EmailUtility::email_verification_for_registration_user('email_registration_verification', $email, $verificationCode);
            } catch (\Exception $e) {
                $success = 0;
            }
        } elseif ($phone != null && addon_activation('otp_system') && (get_sms_template('mobile_registration_verification', 'status') == 1)) {
            try {
                SmsUtility::mobile_registration_verification($phone, $verificationCode);
            } catch (\Exception $e) {
                $success = 0;
            }
        }


        if ($success) {
            return response()->json(['status' => 1, 'message' => translate('Verification code sent successfully.')]);
        } else {
            return response()->json(['status' => 0, 'message' => translate('Verification code sending failed.')]);
        }
    }

    public function regVerifyCodeConfirmation(Request $request)
    {
        $email = isset($request->email) ? $request->email : null;
        $phone = isset($request->phone) ? $request->phone  : null;

        $customerVerification = RegistrationVerificationCode::where('code', $request->code);
        $customerVerification = $request->email != null ?
            $customerVerification->where('email', $email) :
            $customerVerification->where('phone', $phone);
        $customerVerification = $customerVerification->first();
        if ($customerVerification == null) {
            return response()->json(['status' => 0, 'message' => translate('Verification Code did not match')]);
        } else {
            $customerVerification->is_verified = 1;
            $customerVerification->save();
            return response()->json(['status' => 1, 'message' => translate('Verification Successful')]);
        }
    }
}
