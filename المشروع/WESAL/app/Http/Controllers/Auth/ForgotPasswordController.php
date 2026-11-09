<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Illuminate\Http\Request;
use App\Models\User;
use App\Security\PasswordResetCode;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('throttle:password-reset-web')->only('sendResetLinkEmail');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|max:255',
        ]);

        $identity = trim($validated['email']);
        $user = filter_var($identity, FILTER_VALIDATE_EMAIL)
            ? User::where('email', strtolower($identity))->first()
            : User::where('phone', $identity)->first();

        if ($user) {
            $issued = PasswordResetCode::issue(10);
            $user->password_reset_code_hash = $issued['hash'];
            $user->password_reset_code_expires_at = $issued['expires_at'];
            $user->verification_code = null;
            $user->save();

            try {
                if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
                    EmailUtility::password_reset_email($user, $issued['code']);
                } else {
                    SmsUtility::password_reset($user, $issued['code']);
                }
            } catch (\Throwable $exception) {
                \Log::warning('Web password reset delivery failed.', [
                    'user_id' => $user->id,
                    'channel' => filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone',
                    'exception' => get_class($exception),
                ]);

                $user->password_reset_code_hash = null;
                $user->password_reset_code_expires_at = null;
                $user->save();
            }
        }

        // Same response and view for existing and unknown identities to prevent enumeration.
        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            return view('auth.passwords.reset', [
                'email' => $identity,
                'reset_notice' => translate('If the account exists, a reset code has been sent.'),
            ]);
        }

        return view('addons.otp_systems.frontend.auth.passwords.reset_with_phone', [
            'phone' => $identity,
            'reset_notice' => translate('If the account exists, a reset code has been sent.'),
        ]);
    }

}
