@extends('frontend.layouts.app')

@section('content')
<style>
    /* 1. الخلفية الملكية العميقة وإخفاء الفوتر لعدم إزعاج الكيبورد */
    .aiz-main-wrapper { 
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        min-height: 100vh;
    }
    .aiz-header, .aiz-footer, footer, .aiz-mobile-bottom-nav { 
        display: none !important; 
    }

    /* 2. تحويل الصندوق إلى زجاج ملكي (Frosted White & Gold) */
    .card { 
        border: none; 
        border-radius: 20px; 
        background: rgba(255, 255, 255, 0.95) !important; 
        backdrop-filter: blur(20px) saturate(160%);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        border: 1px solid rgba(212, 175, 55, 0.4);
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    }
    
    .card-body { padding: 3rem !important; }

    /* 3. تأثير النص اللامع الملكي (ذهبي وكحلي) */
    @keyframes textShineGold {
        0% { color: var(--secondary); text-shadow: 0 0 5px rgba(212, 175, 55, 0); }
        50% { color: var(--primary); text-shadow: 0 0 20px rgba(212, 175, 55, 0.6); }
        100% { color: var(--secondary); text-shadow: 0 0 5px rgba(212, 175, 55, 0); }
    }
    .shiny-title {
        animation: textShineGold 4s infinite ease-in-out;
        font-weight: 900 !important;
    }

    /* 4. الشريط الكحلي الفخم حول العناوين */
    .royal-navy-strip {
        background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
        border: 1px solid rgba(212, 175, 55, 0.2);
    }
    .royal-navy-strip h1 { color: #ffffff !important; margin: 0; font-size: 1.8rem; }

    /* 5. توحيد النصوص باللون الكحلي الأنيق بدل الأسود */
    #reg-form label, 
    #reg-form p, 
    #reg-form span:not(.white-text):not(.aiz-square-check),
    #reg-form small {
        color: var(--secondary) !important;
        font-weight: 800;
    }

    /* 6. تحسين حقول الإدخال لتلائم الهوية */
    .form-control { 
        border-radius: 12px; 
        padding: 0.75rem 1.2rem; 
        border: 2px solid rgba(15, 23, 42, 0.08) !important; 
        background-color: #f8fafc !important;
        color: var(--secondary) !important;
        font-weight: 700;
    }
    .form-control:focus { 
        background-color: #ffffff !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15) !important;
    }

    /* 7. زر الإرسال المتبلور (ذهبي فخم) */
    .btn-primary.glass-submit-btn { 
        background: linear-gradient(135deg, var(--primary), var(--hov-primary)) !important;
        color: #ffffff !important;
        border: none !important;
        padding: 0.9rem; 
        border-radius: 12px; 
        font-weight: 900; 
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        transition: all 0.3s ease;
    }
    .btn-primary.glass-submit-btn:hover:not(:disabled) { 
        transform: translateY(-3px); 
        box-shadow: 0 12px 25px rgba(212, 175, 55, 0.4);
    }
    .btn-primary.glass-submit-btn:disabled {
        background: #e2e8f0 !important;
        color: #94a3b8 !important;
        box-shadow: none;
    }

    /* 8. التنبيه القانوني الفخم */
    .legal-warning { 
        background-color: #fff5f5; 
        border-right: 5px solid #ef4444; 
        padding: 15px; 
        margin-top: 15px; 
        color: var(--secondary); 
        font-size: 0.85rem; 
        border-radius: 8px;
        border-left: 1px solid #fee2e2;
        border-top: 1px solid #fee2e2;
        border-bottom: 1px solid #fee2e2;
    }

    /* 9. تنسيق قوائم المواليد الصغيرة */
    .dob-group { display: flex; gap: 8px; }
    .dob-group .bootstrap-select { flex: 1; }
    .dob-group select { color: var(--secondary) !important; font-weight: 700; }
    
    /* روابط */
    .text-primary { color: var(--primary) !important; }

    @media (max-width: 768px) {
        .card-body { padding: 1.5rem !important; }
        .dob-group { gap: 4px; }
    }
</style>

<div class="py-4 py-lg-5 text-right" dir="rtl">
    <div class="container">
        <div class="row">
            <div class="col-xxl-6 col-xl-7 col-md-10 mx-auto">
                <div class="card shadow-lg">
                    <div class="card-body">

                        <div class="mb-5 text-center">
                            <div class="royal-navy-strip">
                                <h1 class="h3 shiny-title">انضم إلى عائلة وصال </h1>
                            </div>
                            <p class="fs-15" style="color: #64748b !important;">أهلاً بك في منصتنا الآمنة.. ابدأ الآن رحلة البحث عن نصفك الآخر</p>
                        </div>

                        <form class="form-default" id="reg-form" role="form" action="{{ route('register') }}" method="POST" autocomplete="off">
                            @csrf
                            
                            @php $on_behalves = \App\Models\OnBehalf::all(); @endphp
                            @if ($on_behalves->isNotEmpty())
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="on_behalf">صاحب هذا الحساب هو</label>
                                        <select class="form-control aiz-selectpicker @error('on_behalf') is-invalid @enderror" name="on_behalf" data-live-search="true" required>
                                            @foreach ($on_behalves as $on_behalf)
                                            <option value="{{ $on_behalf->id }}">{{ $on_behalf->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('on_behalf')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="first_name">الاسم الشخصي</label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" id="first_name" placeholder="أدخل اسمك" required autocomplete="off">
                                        @error('first_name')
                                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="last_name">الكنية / اسم العائلة</label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" id="last_name" placeholder="أدخل الكنية" required autocomplete="off">
                                        @error('last_name')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-5">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="gender">الجنس</label>
                                        <select id="gender" class="form-control aiz-selectpicker select_gender" name="gender" required>
                                            <option value="1" selected>ذكر</option>
                                            <option value="2">أنثى</option>
                                        </select>
                                        @error('gender')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="form-group mb-4">
                                        <label class="form-label">تاريخ الميلاد (يوم / شهر / سنة)</label>
                                        <input type="hidden" id="date_of_birth" name="date_of_birth" required>
                                        
                                        <div class="dob-group">
                                            <select id="dob_day" class="form-control aiz-selectpicker" data-live-search="true" title="يوم">
                                                @for ($i = 1; $i <= 31; $i++)
                                                    <option value="{{ sprintf('%02d', $i) }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                            <select id="dob_month" class="form-control aiz-selectpicker" data-live-search="true" title="شهر">
                                                @for ($m = 1; $m <= 12; $m++)
                                                    <option value="{{ sprintf('%02d', $m) }}">{{ sprintf('%02d', $m) }}</option>
                                                @endfor
                                            </select>
                                            <select id="dob_year" class="form-control aiz-selectpicker" data-live-search="true" title="سنة">
                                                @for ($y = date('Y') - 18; $y >= date('Y') - 70; $y--)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        @error('date_of_birth')
                                        <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            @if (addon_activation('otp_system'))
                                <div>
                                    <div id="emailOrPhoneDiv">
                                        <div class="d-flex justify-content-between align-items-end mb-2">
                                            <label class="form-label mb-0" for="email">وسيلة التواصل (هاتف أو بريد)</label>
                                            <button class="btn btn-link p-0 text-primary fs-12 fw-800 text-decoration-none" type="button" onclick="toggleEmailPhone(this)">استخدام البريد الإلكتروني</button>
                                        </div>
                                        <div class="form-group phone-form-group mb-4">
                                            <div class="input-group">
                                                <input type="number" id="phone-code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="{{ old('phone') }}" placeholder="رقم الهاتف" name="phone" autocomplete="off">
                                                @if(get_setting('registration_verification') == '1')
                                                <button class="btn btn-primary px-4" type="button" id="sendOtpPhoneBtn" onclick="sendVerificationCode(this)" style="background: var(--primary); border:none;">تأكيد</button>
                                                @endif
                                            </div>
                                        </div>

                                        <input type="hidden" name="country_code" value="">

                                        <div class="form-group email-form-group mb-4 d-none">
                                            <div class="input-group">
                                                <input type="email" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }} " value="{{ old('email') }}" placeholder="عنوان البريد الإلكتروني" name="email" id="signinAddonEmail" autocomplete="off">
                                                    @if(get_setting('registration_verification') == '1')
                                                    <button class="btn btn-primary px-4" type="button" id="sendOtpBtn" onclick="sendVerificationCode(this)" style="background: var(--primary); border:none;">تحقق</button>
                                                    @endif
                                            </div>
                                            @if ($errors->has('email'))
                                            <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('email') }}</strong></span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group mb-4 d-none">
                                        <label class="form-label" for="verification_code">رمز التحقق المستلم</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control @error('verification_code') is-invalid @enderror border-right-0" name="code" id="verification_code" placeholder="أدخل الرمز" maxlength="6" autocomplete="off">
                                            <span class="btn border border-left-0 bg-white" id="verifyOtpBtn">
                                                <i class="las la-lg la-arrow-right text-primary"></i> 
                                            </span>
                                            @error('otp')
                                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @else
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group mb-4 email-phone-div" id="emailOrPhoneDiv">
                                        <label class="form-label" for="email">عنوان البريد الإلكتروني</label>
                                        <div class="input-group">
                                            <input type="email" class="form-control @error('email') is-invalid @enderror " name="email" id="signinSrEmail" placeholder="example@mail.com" autocomplete="off">
                                                @if(get_setting('registration_verification') == '1')
                                                <button class="btn btn-primary px-4" type="button" id="sendOtpBtn" onclick="sendVerificationCode()" style="background: var(--primary); border:none;">تأكيد</button>
                                                @endif
                                        </div>
                                        @error('email')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="password">كلمة المرور</label>
                                        {{-- منع الإكمال التلقائي هنا --}}
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••" required autocomplete="new-password">
                                        <small class="text-muted opacity-60">قوية، 8 رموز على الأقل</small>
                                        @error('password')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="password-confirm">تأكيد كلمة المرور</label>
                                        {{-- منع الإكمال التلقائي هنا --}}
                                        <input type="password" class="form-control" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>

                            @if(addon_activation('referral_system'))
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group mb-4">
                                        <label class="form-label" for="email">كود الإحالة (اختياري)</label>
                                        <input type="text" class="form-control" placeholder="إذا كان لديك كود إحالة أدخله هنا" name="referral_code" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="mb-4 pt-2">
                                <div class="d-flex align-items-center mb-2">
                                    <label class="aiz-checkbox mb-0">
                                        <input type="checkbox" name="checkbox_example_1" required>
                                        <span class="aiz-square-check mr-2"></span>
                                    </label>
                                    <span class="opacity-90 fs-14 ml-2 fw-800">بضغطك على إنشاء الحساب، أنت توافق على 
                                        <a href="{{ env('APP_URL').'/terms-conditions' }}" target="_blank" class="text-primary underline fw-900">شروط الخدمة واتفاقية الخصوصية</a>.
                                    </span>
                                </div>
                                
                                <div class="legal-warning shadow-sm mt-3">
                                    <i class="las la-balance-scale mr-2 text-danger fs-18"></i>
                                    <strong class="text-danger">تنبيه قانوني:</strong> في حال مخالفة الشروط أو التعرض للمشتركين بأي شكل من أشكال الإساءة، فإنك تعرض نفسك للمساءلة القانونية والحظر الدائم.
                                </div>
                            </div>

                            <div class="mb-4">
                                <button type="submit" id="createAccountBtn" class="btn btn-block glass-submit-btn fs-18">إتمام عملية التسجيل</button>
                            </div>
                            
                            @if(get_setting('google_login_activation') == 1 || get_setting('facebook_login_activation') == 1 || get_setting('twitter_login_activation') == 1 || get_setting('apple_login_activation') == 1)
                            <div class="mb-5 text-center">
                                <div class="separator mb-4">
                                    <span class="bg-transparent px-3 fs-13 text-secondary fw-800">أو سجل بسرعة عبر</span>
                                </div>
                                <ul class="list-inline social colored text-center">
                                    @if(get_setting('facebook_login_activation') == 1)
                                    <li class="list-inline-item">
                                        <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="facebook shadow-sm"><i class="lab la-facebook-f"></i></a>
                                    </li>
                                    @endif
                                    @if(get_setting('google_login_activation') == 1)
                                    <li class="list-inline-item">
                                        <a href="{{ route('social.login', ['provider' => 'google']) }}" class="google shadow-sm"><i class="lab la-google"></i></a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                            @endif

                            <div class="text-center pt-4 border-top" style="border-color: rgba(15, 23, 42, 0.1) !important;">
                                <p class="mb-2 fs-14" style="color: #64748b !important;">مسجل لدينا بالفعل؟</p>
                                <a href="{{ route('login') }}" class="fw-900 text-primary fs-16 text-decoration-none">تسجيل الدخول من هنا <i class="las la-arrow-left fs-14"></i></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // منطق تجميع التاريخ من القوائم المنسدلة الثلاث (يوم-شهر-سنة)
            const daySelect = document.getElementById('dob_day');
            const monthSelect = document.getElementById('dob_month');
            const yearSelect = document.getElementById('dob_year');
            const hiddenDobInput = document.getElementById('date_of_birth');

            function combineDate() {
                if(daySelect.value && monthSelect.value && yearSelect.value) {
                    hiddenDobInput.value = yearSelect.value + '-' + monthSelect.value + '-' + daySelect.value;
                }
            }

            if(daySelect && monthSelect && yearSelect) {
                $(daySelect).on('change', combineDate);
                $(monthSelect).on('change', combineDate);
                $(yearSelect).on('change', combineDate);
            }
        });
    </script>
    
    @if(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_user_register') == 1)
        @include('partials.recaptcha', ['action' => 'recaptcha_user_register','form_id' => 'reg-form'])
    @endif
    
    @if(addon_activation('otp_system'))
        @include('partials.emailOrPhone')
    @endif

    @if (get_setting('registration_verification'))
        @include('partials.verifyEmailOrPhone')
    @endif

    <script type="text/javascript">
        const regVerifyRequired = {{get_setting('registration_verification') ? 'true' : 'false' }};
        const createAccountBtn   = $('#createAccountBtn');
        const termsCheckboxInput = $('input[name="checkbox_example_1"]');
        
        function toggleCreateAccountBtn() {
            const isTermsChecked = termsCheckboxInput.is(':checked');
            const isRegVerified  = regVerifyRequired ? (typeof verifyBtn !== 'undefined' && verifyBtn && verifyBtn.classList.contains('disabled')) : true;
            createAccountBtn.prop('disabled', !(isTermsChecked && isRegVerified));
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleCreateAccountBtn();
            termsCheckboxInput.on('change', toggleCreateAccountBtn);
            
            // إضافة سبينر عند الضغط على زر التسجيل ليعرف المستخدم أن العملية جارية
            $('#reg-form').on('submit', function() {
                if(!createAccountBtn.prop('disabled')) {
                    createAccountBtn.html('<i class="las la-spinner la-spin mr-2"></i> جاري إتمام التسجيل...');
                }
            });
        });
    </script>
@endsection