<?php

namespace App\Http\Controllers\Auth;

use Notification;
use App\Models\User;
use App\Models\Member;
use App\Models\Package;
use App\Models\RegistrationVerificationCode;
use App\Rules\RecaptchaRule;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Utility\EmailUtility;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Notifications\DbStoreNotification;
use Kutia\Larafirebase\Facades\Larafirebase;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Http\Controllers\OTPVerificationController;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * 🛑 التوجيه بعد التسجيل مباشرة إلى الصفحة الرئيسية (Home)
     */
    protected $redirectTo = '/'; 

    public function __construct()
    {
        //
    }

    public function showRegistrationForm()
    {
        abort(404);
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'on_behalf'            => 'nullable|integer',
            'first_name'           => ['required', 'string', 'max:255'],
            'last_name'            => ['required', 'string', 'max:255'],
            'gender'               => 'required',
            'date_of_birth'        => 'required|date',
            'phone'                 => 'required_without:email|nullable|string|unique:users',
            'email'                 => 'required_without:phone|nullable|email|unique:users',
            'password'             => ['required', 'string', 'min:8', 'confirmed'],
            'g-recaptcha-response' => [
                Rule::when(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_user_register') == 1 , ['required', new RecaptchaRule()], ['sometimes'])
            ],
            'checkbox_example_1'   => ['required', 'string'],
        ],
        [
            'first_name.required' => translate('الاسم الأول مطلوب'),
            'last_name.required' => translate('اسم العائلة مطلوب'),
            'gender.required' => translate('الجنس مطلوب'),
            'date_of_birth.required' => translate('تاريخ الميلاد مطلوب'),
            'email.required' => translate('البريد الإلكتروني مطلوب'),
            'email.unique' => translate('البريد الإلكتروني مستخدم مسبقاً'),
            'phone.unique' => translate('رقم الهاتف مستخدم مسبقاً'),
            'password.required' => translate('كلمة المرور مطلوبة'),
            'password.confirmed' => translate('كلمات المرور غير متطابقة'),
            'checkbox_example_1.required'    => translate('يجب الموافقة على الشروط والأحكام.'),
        ]);
    }

    protected function create(array $data)
    {
        $approval = get_setting('member_verification') == 1 ? 0 : 1;
        
        if (filter_var($data['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            $user = User::create([
                'first_name'  => $data['first_name'],
                'last_name'   => $data['last_name'],
                'membership'  => 1,
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'code'        => unique_code(),
                'approved'    => $approval,
            ]);
        } else {
            if (addon_activation('otp_system') && isset($data['phone'])) {
                $cleanPhone = preg_replace('/\D+/', '', $data['phone']);
                $user = User::create([
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'membership'  => 1,
                    'phone'       => '+' . ($data['country_code'] ?? '') . $cleanPhone,
                    'password'    => Hash::make($data['password']),
                    'code'        => unique_code(),
                    'approved'    => $approval,
                    'verification_code' => random_int(100000, 999999)
                ]);
            } else {
                $user = User::create([
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'membership'  => 1,
                    'password'    => Hash::make($data['password']),
                    'code'        => unique_code(),
                    'approved'    => $approval,
                ]);
            }
        }
        
        if (addon_activation('referral_system') && isset($data['referral_code']) && $data['referral_code'] != null) {
            $reffered_user = User::where('code', '!=', null)->where('code', $data['referral_code'])->first();
            if ($reffered_user != null) {
                $user->referred_by = $reffered_user->id;
                $user->save();
            }
        }

        $member                             = new Member;
        $member->user_id                    = $user->id;
        $member->save();

        $member->gender                     = $data['gender'] ?? 1;
        $member->on_behalves_id             = $data['on_behalf'] ?? null;
        $member->birthday                   = date('Y-m-d', strtotime($data['date_of_birth']));

        $target_package_id = ($data['gender'] == 2) ? 11 : 1;
        $package = Package::find($target_package_id) ?? Package::find(1);
        
        $member->current_package_id             = $package->id;
        $member->remaining_interest             = $package->express_interest;
        $member->remaining_photo_gallery        = $package->photo_gallery;
        $member->remaining_contact_view         = $package->contact;
        $member->remaining_profile_viewer_view  = $package->profile_viewers_view;
        $member->remaining_profile_image_view   = $package->profile_image_view;
        $member->remaining_gallery_image_view   = $package->gallery_image_view;
        $member->auto_profile_match             = $package->auto_profile_match;
        $member->package_validity               = Date('Y-m-d', strtotime($package->validity . " days"));
        $member->save();

        if (isset($data['email']) && env('MAIL_USERNAME') != null) {
            $account_oppening_email = EmailTemplate::where('identifier', 'account_oppening_email')->first();
            if ($account_oppening_email && $account_oppening_email->status == 1) {
                EmailUtility::account_oppening_email($user->id, $data['password']);
            }
        }

        return $user;
    }

    public function register(Request $request)
    {
        // Public website registration is disabled. Mobile API registration
        // is implemented by its separate API controller and routes.
        abort(404);
    }

    protected function registered(Request $request, $user)
    {
        if ($user->email == null && $user->email_verified_at == null) {
            return redirect()->route('verification');
        } else {
            return redirect('/'); 
        }
    }
}