<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\SecondEmailVerifyMailManager;
use App\Models\AdditionalAttribute;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use Kutia\Larafirebase\Facades\Larafirebase;
use App\Models\User;
use App\Models\Member;
use App\Models\PhysicalAttribute;
use App\Models\SpiritualBackground;
use App\Models\Career;
use App\Models\Address;
use App\Models\HappyStory;
use App\Models\IgnoredUser;
use App\Models\ProfileMatch;
use App\Models\ProfileViewer;
use App\Models\RegistrationVerificationCode;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Notification;
use Hash;
use Artisan;
use Mail;
use Auth;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Password recovery must be rate-limited without changing public page behavior.
        $this->middleware('throttle:password-reset-web')->only('reset_password_with_code');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // واجهة الموقع العامة تعرض الجنسين لكل الزوار، ولا تطبق فلاتر العضو المسجل.
        // الصور نفسها تبقى خاضعة لدالة show_profile_picture وإعدادات الخصوصية الحالية.
        $publicMembers = User::query()
            ->with('member')
            ->where('user_type', 'member')
            ->where('approved', 1)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member');

        // حالة العضوية قد تتأخر عن حالة الاشتراك في users.membership.
        // لذلك نتحقق من الباقة الحالية نفسها: مدفوعة، مفعّلة، غير محذوفة، وصالحة اليوم.
        // العلاقة member.package مرتبطة بـ members.current_package_id.
        $featuredMembers = (clone $publicMembers)
            ->with('member.package')
            ->whereHas('member', function ($query) {
                $query->whereNotNull('current_package_id')
                    ->whereNotNull('package_validity')
                    ->whereDate('package_validity', '>=', now()->toDateString());
            })
            ->whereHas('member.package', function ($query) {
                $query->where('active', 1)
                    ->where('price', '>', 0)
                    ->whereNull('packages.deleted_at');
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // الجنس: 1 ذكور، 2 إناث. نعرض أحدث الملفات المنشورة من كل فئة.
        $femaleMembers = (clone $publicMembers)
            ->whereHas('member', function ($query) {
                $query->where('gender', 2);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $maleMembers = (clone $publicMembers)
            ->whereHas('member', function ($query) {
                $query->where('gender', 1);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // روابط المتاجر مضبوطة مسبقاً من إعدادات تذييل الموقع؛ تظل الأزرار "قريباً"
        // تلقائياً ما دام التطبيق لم يُنشر ولم يُدخل رابط المتجر بعد.
        $playStoreUrl = trim((string) get_setting('footer_play_store_link'));
        $appStoreUrl = trim((string) get_setting('footer_app_store_link'));
        $isSafeStoreUrl = function ($url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return false;
            }

            return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
        };
        $playStoreUrl = $isSafeStoreUrl($playStoreUrl) ? $playStoreUrl : '';
        $appStoreUrl = $isSafeStoreUrl($appStoreUrl) ? $appStoreUrl : '';
        $appDownloadUrl = $playStoreUrl ?: $appStoreUrl;

        return view('frontend.index', compact(
            'featuredMembers',
            'femaleMembers',
            'maleMembers',
            'playStoreUrl',
            'appStoreUrl',
            'appDownloadUrl'
        ));
    }


    public function admin_login()
    {
        if (auth()->user() != null && (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff')) {
            return redirect()->route('admin.dashboard');
        } else {
            return view("auth.login");
        }
    }

    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('frontend.user_login');
    }


    public function admin_dashboard()
    {
        return view('admin.dashboard');
    }
    
    // Manage Admin Profile
    public function admin_profile_update(Request $request, $id)
    {
        $admin = User::findOrFail($id);
        $admin->first_name = $request->first_name;
        $admin->last_name = $request->last_name;
        if ($request->new_password != null && ($request->new_password == $request->confirm_password)) {
            $admin->password = Hash::make($request->new_password);
        }
        //$admin->save();
        if ($admin->save()) {
            flash(translate('Your Profile has been updated successfully!'))->success();
            return back();
        }

        flash(translate('Sorry! Something went wrong.'))->error();
        return back();
    }

    public function dashboard()
    {
        $user = auth()->user();
        if ($user->user_type == 'member') {
            
            if($user->blocked == 1){
                return redirect()->route('user.blocked');
            }

            $similar_profiles = ProfileMatch::orderBy('match_percentage', 'desc')
                ->where('user_id', $user->id)
                ->where('match_percentage', '>=', 50)
                ->limit(20);

            $ignored_to = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $similar_profiles = $similar_profiles->whereNotIn('match_id', $ignored_to);
            }
            $ignored_by_ids = IgnoredUser::where('user_id', $user->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $similar_profiles = $similar_profiles->whereNotIn('match_id', $ignored_by_ids);
            }
            $similar_profiles = $similar_profiles->get();

            return view('frontend.member.dashboard', compact('similar_profiles'));
        } else {
            abort(404);
        }
    }

    public function user_account_blocked()
    {
        return view('frontend.user_account_blocked_msg');
    }

    public function happy_stories()
    {
        $happy_stories = HappyStory::where('approved', 1)->latest()->paginate(12);
        return view('frontend.happy_stories.index', compact('happy_stories'));
    }

    public function story_details($id)
    {
        $happy_story = HappyStory::findOrFail($id);
        return view('frontend.happy_stories.story_details', compact('happy_story'));
    }

    public function member_listing(Request $request)
    {
        $age_from       = ($request->age_from != null) ? $request->age_from : null;
        $age_to         = ($request->age_to != null) ? $request->age_to : null;
        $member_code    = ($request->member_code != null) ? $request->member_code : null;
        $matital_status = ($request->marital_status != null) ? $request->marital_status : null;
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


        $users = User::orderBy('created_at', 'desc')
            ->where('user_type', 'member')
            ->where('blocked', 0)
            ->where('deactivated', 0);

        // --- تعديل الحماية للزوار: تطبيق الفلاتر الخاصة بالمستخدم المسجل فقط ---
        if (Auth::check() && Auth::user()->user_type == 'member') {
            $users = $users->where('id', '!=', Auth::user()->id);

            // Gender Check
            if (Auth::user()->member) {
                $user_ids = Member::where('gender', '!=', Auth::user()->member->gender)->pluck('user_id')->toArray();
                $users = $users->WhereIn('id', $user_ids);
            }

            // Ignored member and ignored by member check
            $users = $users->WhereNotIn("id", function ($query) {
                $query->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            })
            ->WhereNotIn("id", function ($query) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('ignored_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            });
        }
        // --- نهاية التعديل ---

        // Membership Check
        if ($member_type == 1 || $member_type == 2) {
            $users = $users->where('membership', $member_type);
        }

        // Member verification Check
        if (get_setting('member_verification') == 1) {
            $users = $users->where('approved', 1);
        }

        // Sort By age
        if (!empty($age_from)) {
            $age = $age_from + 1;
            $start = date('Y-m-d', strtotime("- $age years"));
            $user_ids = Member::where('birthday', '<=', $start)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }
        if (!empty($age_to)) {
            $age = $age_to + 1;
            $end = date('Y-m-d', strtotime("- $age years +1 day"));
            $user_ids = Member::where('birthday', '>=', $end)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }

        // Search by Member Code
        if (!empty($member_code)) {
            $users = $users->where('code', $member_code);
        }

        // Sort by Matital Status
        if ($matital_status != null) {
            $user_ids = Member::where('marital_status_id', $matital_status)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }

        // Sort By religion
        if (!empty($sub_caste_id)) {
            $user_ids = SpiritualBackground::where('sub_caste_id', $sub_caste_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($caste_id)) {
            $user_ids = SpiritualBackground::where('caste_id', $caste_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($religion_id)) {
            $user_ids = SpiritualBackground::where('religion_id', $religion_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        }
        // Profession
        elseif (!empty($profession)) {
            $user_ids = Career::where('designation', 'like', '%' . $profession . '%')->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        }

        // Sort By location
        if (!empty($city_id)) {
            $user_ids = Address::where('city_id', $city_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($state_id)) {
            $user_ids = Address::where('state_id', $state_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        } elseif (!empty($country_id)) {
            $user_ids = Address::where('country_id', $country_id)->pluck('user_id')->toArray();
            $users = $users->WhereIn('id', $user_ids);
        }

        // Sort By Mother Tongue
        if ($mother_tongue != null) {
            $user_ids = Member::where('mothere_tongue', $mother_tongue)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }

        // Sort by Height
        if (!empty($min_height)) {
            $user_ids = PhysicalAttribute::where('height', '>=', $min_height)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }
        if (!empty($max_height)) {
            $user_ids = PhysicalAttribute::where('height', '<=', $max_height)->pluck('user_id')->toArray();
            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }
        }

        $users = $users->paginate(10);
        return view('frontend.member.member_listing.index', compact('users', 'age_from', 'age_to', 'member_code', 'matital_status', 'religion_id', 'caste_id', 'sub_caste_id', 'mother_tongue', 'profession', 'country_id', 'state_id', 'city_id', 'min_height', 'max_height', 'member_type'));
    }

    /**
     * Retired legacy activation callback previously logged arbitrary visitors
     * into the first administrator account when an external service returned
     * a specific response. Never expose that behavior over HTTP.
     */
    public function profile_edit(Request $request)
    {
        abort(404);
    }


    // My shortlistd
    public function my_shortlists()
    {
        $shortlists = Member::where('user_id', Auth::user()->id)->first()->short_listed_users;
        return view('frontend.member.my_shortlists', compact('shortlists'));
    }

    // --- تم التعديل لمنع الأخطاء للزوار (Guest) مع بقاء الوظائف للأعضاء المسجلين ---
    public function view_member_profile($id)
    {
        $authUser = auth()->user();
        $user = User::findOrFail($id);
        
        $similar_profiles = collect(); // افتراضياً فارغة للزوار

        // تنفيذ كود الخوارزميات (البروفايلات المشابهة وتسجيل الزيارة) فقط إذا كان هناك مستخدم مسجل دخول
        if ($authUser && $authUser->user_type == 'member') {
            $similar_profiles_query = ProfileMatch::orderBy('match_percentage', 'desc')
                ->where('user_id', $authUser->id)
                ->where('match_id', '!=', $id)
                ->where('match_percentage', '>=', 50)
                ->limit(20);

            $ignored_to = IgnoredUser::where('ignored_by', $authUser->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $similar_profiles_query = $similar_profiles_query->whereNotIn('match_id', $ignored_to);
            }

            $ignored_by_ids = IgnoredUser::where('user_id', $authUser->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $similar_profiles_query = $similar_profiles_query->whereNotIn('match_id', $ignored_by_ids);
            }
            $similar_profiles = $similar_profiles_query->get();

            // Profile view data store
            if($user->id != $authUser->id){
                $profileViewed = ProfileViewer::where('user_id', $user->id)->where('viewed_by', $authUser->id)->first();
                if($profileViewed == null){
                    if(package_validity($user->id) && $user->member->remaining_profile_viewer_view > 0){
                        ProfileViewer::create([
                            'user_id' => $user->id,
                            'viewed_by' => $authUser->id
                        ]);
                        $usermember = $user->member;
                        $usermember->remaining_profile_viewer_view = $usermember->remaining_profile_viewer_view - 1;
                        $usermember->save();

                        // Profile viewed Notification for member
                        try {
                            $notify_type = 'profile_viewed';
                            $notify_id = unique_notify_id();
                            $notify_by = $authUser->id;
                            $info_id = $user->id;
                            $message = $authUser->first_name . ' ' . $authUser->last_name . ' ' . translate(' has viewed your profile.');
                            $route = route('member_profile', $authUser->id);
            
                            // fcm 
                            if (get_setting('firebase_push_notification') == 1) {
                                $fcmTokens = User::where('id', $user->id)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                                self::sendFirebaseNotification($fcmTokens, $user, $notify_type, $message, $notify_by);
                            }
                            // end of fcm
            
                            Notification::send($user, new DbStoreNotification($notify_type, $notify_id, $notify_by, $info_id, $message, $route));
                        } catch (\Exception $e) {
                            //
                        }
                    }
                }
            }
        }
        
        $additional_attributes  = AdditionalAttribute::where('status', 1)->get();

        return view('frontend.member.public_profile.index', compact('user', 'similar_profiles', 'additional_attributes'));
    }
    // --- نهاية التعديل ---

    // Ajax call
    public function new_verify(Request $request)
    {
        $email = $request->email;
        if (User::where('email', $email)->count() == 0) {
            $response['status'] = 2;
            $response['message'] = 'Email already exists!';
            return json_encode($response);
        }

        $response = $this->send_email_change_verification_mail($request, $email);
        return json_encode($response);
    }

    // Form request
    public function update_email(Request $request)
    {
        $email = $request->email;
        if (User::where('email', $email)->count() == 0) {
            $this->send_email_change_verification_mail($request, $email);
            flash(translate('A verification mail has been sent to the mail you provided us with.'))->success();
            return back();
        }

        flash(translate('Email already exists!'))->warning();
        return back();
    }

    public function send_email_change_verification_mail($request, $email)
    {
        $response['status'] = 0;
        $response['message'] = 'Unknown';

        $verification_code = Str::random(32);

        $array['subject'] = 'Email Verification';
        $array['from'] = env('MAIL_USERNAME');
        $array['content'] = 'Verify your account';
        $array['link'] = route('email_change.callback') . '?new_email_verificiation_code=' . $verification_code . '&email=' . $email;
        $array['sender'] = Auth::user()->name;
        $array['details'] = "Email Second";

        $user = Auth::user();
        $user->new_email_verificiation_code = $verification_code;
        $user->save();

        try {
            Mail::to($email)->queue(new SecondEmailVerifyMailManager($array));

            $response['status'] = 1;
            $response['message'] = translate("Your verification mail has been Sent to your email.");
        } catch (\Exception $e) {
            return $e->getMessage();
            $response['status'] = 0;
            $response['message'] = $e->getMessage();
        }

        return $response;
    }

    public function email_change_callback(Request $request)
    {

        if ($request->has('new_email_verificiation_code') && $request->has('email')) {

            $verification_code_of_url_param =  $request->input('new_email_verificiation_code');
            $user = User::where('new_email_verificiation_code', $verification_code_of_url_param)->first();

            if ($user != null) {

                $user->email = $request->input('email');
                $user->new_email_verificiation_code = null;
                $user->save();

                auth()->login($user, true);

                flash(translate('Email Changed successfully'))->success();
                return redirect()->route('dashboard');
            }
        }

        flash(translate('Email was not verified. Please resend your mail!'))->error();
        return redirect()->route('dashboard');
    }

    public function reset_password_with_code(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'code' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $validated['email'])->first();
        $storedCode = $user ? (string) $user->verification_code : '';

        if (!$user || $storedCode === ''
            || !hash_equals($storedCode, (string) $validated['code'])) {
            flash(translate('Verification code mismatch'))->error();
            return back();
        }

        $user->password = Hash::make($validated['password']);
        $user->verification_code = null;
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Revoke mobile bearer tokens after a password reset.
        $user->tokens()->delete();

        auth()->login($user, true);
        $request->session()->regenerate();

        flash(translate('Password updated successfully'))->success();

        if (in_array($user->user_type, ['admin', 'staff'], true)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('home');
    }

    function clearCache()
    {
        Artisan::call('optimize:clear');
        flash(translate('Cache cleared successfully'))->success();
        return back();
    }

    public function user_remaining_package_value(Request $request)
    {
        $colmn_name = $request->colmn_name;
        $value = Member::where('user_id', $request->id)->first()->$colmn_name;
        return $value;
    }

    // fcm
    public function updateToken(Request $request)
    {
        try {
            $request->user()->update(['fcm_token' => $request->fcm_token]);
            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public static function sendFirebaseNotification($fcmTokens = null, $notify_user, $notify_type, $message, $notify_by = null)
    {
        // send firebase notification for mobile app
        if ($notify_user->fcm_token != null) {
            $data = (object)[];
            $data->fcm_token = $notify_user->fcm_token;
            $data->title = $notify_type;
            $data->text = $message;
            $data->notify_by = $notify_by;
            FirbaseNotification::send($data);
        }
        // end of  firebase notification

        Larafirebase::withTitle(str_replace("_", " ", $notify_type))
            ->withBody($message)
            ->sendMessage($fcmTokens);
    }

    public function sendRegVerificationCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:24|required_without:email',
            'country_code' => 'nullable|string|max:8',
        ]);

        $email = !empty($validated['email']) ? strtolower(trim($validated['email'])) : null;
        $phone = !empty($validated['phone'])
            ? '+' . ltrim(preg_replace('/\\D+/', '', (string) ($validated['country_code'] ?? '')), '')
                . preg_replace('/\\D+/', '', $validated['phone'])
            : null;

        if ($email && User::where('email', $email)->exists()) {
            return response()->json(['status' => 0, 'message' => translate('Email already exists.')], 409);
        }
        if ($phone && User::where('phone', $phone)->exists()) {
            return response()->json(['status' => 0, 'message' => translate('Phone already exists.')], 409);
        }

        $verificationCode = (string) random_int(100000, 999999);
        $codeHash = password_hash($verificationCode, PASSWORD_DEFAULT);
        if (!is_string($codeHash) || $codeHash === '') {
            return response()->json([
                'status' => 0,
                'message' => translate('Unable to create a secure verification code.'),
            ], 503);
        }

        $customerVerification = RegistrationVerificationCode::updateOrCreate(
            ['email' => $email, 'phone' => $phone],
            [
                // Keep a non-secret placeholder in the legacy column for schema compatibility.
                'code' => random_int(100000, 999999),
                'code_hash' => $codeHash,
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
            \\Log::warning('Web registration verification delivery failed.', [
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
            'country_code' => 'nullable|string|max:8',
            'code' => ['required', 'string', 'regex:/^\\d{6}$/'],
        ]);

        $query = RegistrationVerificationCode::where('expires_at', '>', now())
            ->where('is_verified', 0);

        if (!empty($validated['email'])) {
            $query->where('email', strtolower(trim($validated['email'])));
        } else {
            $phone = '+' . ltrim(preg_replace('/\\D+/', '', (string) ($validated['country_code'] ?? '')), '')
                . preg_replace('/\\D+/', '', $validated['phone']);
            $query->where('phone', $phone);
        }

        $customerVerification = $query->latest('id')->first();
        if (!$customerVerification
            || !is_string($customerVerification->code_hash)
            || !password_verify($validated['code'], $customerVerification->code_hash)) {
            return response()->json([
                'status' => 0,
                'message' => translate('Verification Code did not match or has expired.'),
            ], 422);
        }

        $customerVerification->is_verified = 1;
        $customerVerification->code_hash = null;
        $customerVerification->save();

        return response()->json([
            'status' => 1,
            'message' => translate('Verification Successful'),
        ]);
    }
}