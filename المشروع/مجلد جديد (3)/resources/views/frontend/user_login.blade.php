@extends('frontend.layouts.app')

@section('content')
<style>
    /* 1. الخلفية الملكية العميقة (Midnight Blue) */
    .aiz-main-wrapper { 
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        min-height: 100vh;
    }

    /* 2. بطاقة الزجاج الملكي (Royal Frosted Glass) */
    .royal-glass-card { 
        border: none; 
        border-radius: 24px; 
        background: rgba(255, 255, 255, 0.95) !important; 
        backdrop-filter: blur(20px) saturate(160%);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        border: 1px solid rgba(212, 175, 55, 0.4);
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        overflow: hidden;
    }
    
    .royal-glass-card .card-body { padding: 3.5rem 3rem !important; }

    /* 3. تأثير النص الذهبي اللامع */
    @keyframes textShineGold {
        0% { color: var(--secondary); text-shadow: 0 0 5px rgba(212, 175, 55, 0); }
        50% { color: var(--primary); text-shadow: 0 0 20px rgba(212, 175, 55, 0.6); }
        100% { color: var(--secondary); text-shadow: 0 0 5px rgba(212, 175, 55, 0); }
    }
    .shiny-title {
        animation: textShineGold 4s infinite ease-in-out;
        font-weight: 900 !important;
    }

    /* 4. الشريط الكحلي الملكي حول العناوين */
    .royal-navy-strip {
        background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
        padding: 18px;
        border-radius: 16px;
        margin-bottom: 25px;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
        border: 1px solid rgba(212, 175, 55, 0.2);
    }
    .royal-navy-strip h1 { color: #ffffff !important; margin: 0; font-size: 1.6rem; letter-spacing: 0.5px; }

    /* 5. توحيد النصوص باللون الكحلي الأنيق بدل الأسود */
    .royal-glass-card label, 
    .royal-glass-card p, 
    .royal-glass-card span,
    .royal-glass-card a:not(.btn),
    .royal-glass-card small {
        color: var(--secondary) !important;
        font-weight: 800;
    }

    /* 6. تحسين حقول الإدخال لتلائم الهوية */
    .royal-input { 
        border-radius: 14px !important; 
        padding: 0.85rem 1.2rem !important; 
        border: 2px solid rgba(15, 23, 42, 0.08) !important; 
        background-color: #f8fafc !important;
        color: var(--secondary) !important;
        font-weight: 700;
        font-size: 15px;
        height: auto !important;
        transition: all 0.3s;
    }
    .royal-input:focus { 
        background-color: #ffffff !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15) !important;
    }

    /* 7. زر الدخول الذهبي الفخم */
    .btn-royal-submit { 
        background: linear-gradient(135deg, var(--primary), var(--hov-primary)) !important;
        color: #ffffff !important;
        padding: 1rem !important; 
        border-radius: 14px !important; 
        font-weight: 900 !important; 
        font-size: 16px;
        border: none !important;
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        transition: all 0.3s ease;
    }
    .btn-royal-submit:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 12px 25px rgba(212, 175, 55, 0.4);
    }

    /* أيقونات السوشيال ميديا */
    .social.colored .list-inline-item a {
        width: 45px;
        height: 45px;
        line-height: 45px;
        border-radius: 50%;
        font-size: 18px;
        transition: 0.3s;
    }
    .social.colored .list-inline-item a:hover {
        transform: translateY(-3px);
    }

    /* فواصل السوشيال ميديا */
    .separator span { background: #fff !important; padding: 0 15px; }

    @media (max-width: 768px) {
        .royal-glass-card .card-body { padding: 2.5rem 1.5rem !important; }
        .royal-navy-strip h1 { font-size: 1.4rem; }
    }
</style>

<div class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-xxl-5 col-xl-6 col-lg-7 col-md-9 mx-auto">
                <div class="card royal-glass-card text-right" dir="rtl">
                    <div class="card-body">
                        
                        <div class="mb-5 text-center">
                            <div class="royal-navy-strip">
                                <h1 class="h3 shiny-title">تسجيل الدخول إلى حسابك</h1>
                            </div>
                            <p class="fs-15 mb-0" style="color: #64748b !important;">أهلاً بك مجدداً في منصة وصال</p>
                        </div>

                        <form id="myForm" class="" method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            {{-- حقل البريد الإلكتروني أو الهاتف --}}
                            <div class="form-group mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fs-14" for="email">
                                        {{ addon_activation('otp_system') ? 'ادخل الايميل الذي تم التسجيل فيه' : 'البريد الإلكتروني' }}
                                        </label>
                                    @if (addon_activation('otp_system'))
                                        <button class="btn btn-link p-0 fs-12 fw-800 text-decoration-none" style="color: var(--primary) !important;" type="button" onclick="toggleEmailPhone(this)">
                                            استخدام البريد الإلكتروني بدلاً من ذلك
                                        </button>
                                    @endif
                                </div>

                                @if (addon_activation('otp_system'))
                                    <input type="hidden" name="email" id="finalInput" value="">
                                    <div class="form-group phone-form-group mb-1">
                                        <input type="number" id="phone-code" class="form-control royal-input {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="أدخل رقم الهاتف" autocomplete="off">
                                        @if ($errors->has('email'))
                                            <span class="invalid-feedback fw-700 mt-2" role="alert">{{ $errors->first('email') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group email-form-group mb-1 d-none">
                                        <input type="email" id="email" class="form-control royal-input {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="أدخل البريد الإلكتروني" autocomplete="off">
                                        @if ($errors->has('email'))
                                            <span class="invalid-feedback fw-700 mt-2" role="alert">{{ $errors->first('email') }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="form-group email-form-group mb-1">
                                        <input type="email" name="email" id="email" class="form-control royal-input {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="أدخل الايميل الذي تم التسجيل فيه" autocomplete="of">
                                        @if ($errors->has('email'))
                                            <span class="invalid-feedback fw-700 mt-2" role="alert">{{ $errors->first('email') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- حقل كلمة المرور --}}
                            <div class="form-group mb-4">
                                <label class="form-label fs-14 mb-2" for="password">كلمة المرور</label>
                                <input type="password" class="form-control royal-input @error('password') is-invalid @enderror" name="password" id="password" placeholder="********" required>
                                @error('password')
                                    <span class="invalid-feedback fw-700 mt-2" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- تذكرني ونسيت كلمة المرور --}}
                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <label class="aiz-checkbox mb-0">
                                    <input type="checkbox" name="remember" checked>
                                    <span class="fs-13 fw-800 ml-2">تذكرني دائماً</span>
                                    <span class="aiz-square-check"></span>
                                </label>
                                <a class="text-decoration-none fs-13 fw-800" href="{{ route('password.request') }}" style="color: var(--primary) !important;">هل نسيت كلمة المرور؟</a>
                            </div>

                            {{-- زر الدخول --}}
                            <div class="mb-4 mt-5">
                                <button type="submit" class="btn btn-block btn-royal-submit"><i class="las la-sign-in-alt mr-2 fs-20"></i> تسجيل الدخول لحسابي</button>
                            </div>
                        </form>

                        {{-- الدخول عبر السوشيال ميديا --}}
                        @if (get_setting('google_login_activation') == 1 || get_setting('facebook_login_activation') == 1 || get_setting('twitter_login_activation') == 1 || get_setting('apple_login_activation') == 1)
                            <div class="separator mb-4 mt-4">
                                <span class="px-3 fs-13 fw-800" style="color: #94a3b8 !important;">أو سجل دخولك عبر</span>
                            </div>
                            <ul class="list-inline social colored text-center mb-5">
                                @if (get_setting('facebook_login_activation') == 1)
                                    <li class="list-inline-item">
                                        <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="facebook shadow-sm"><i class="lab la-facebook-f"></i></a>
                                    </li>
                                @endif
                                @if (get_setting('google_login_activation') == 1)
                                    <li class="list-inline-item">
                                        <a href="{{ route('social.login', ['provider' => 'google']) }}" class="google shadow-sm"><i class="lab la-google"></i></a>
                                    </li>
                                @endif
                            </ul>
                        @endif

                        {{-- إنشاء حساب جديد --}}
                        <div class="text-center pt-4 border-top" style="border-color: rgba(15, 23, 42, 0.05) !important;">
                            <p class="mb-2 fs-14" style="color: #64748b !important;">ليس لديك حساب بعد؟</p>
                            <a href="{{ route('register') }}" class="fw-900 fs-16 text-decoration-none" style="color: var(--primary) !important;">
                                إنشاء حساب جديد مجاناً <i class="las la-arrow-left fs-14"></i>
                            </a>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        var isOTPActive = {{ addon_activation('otp_system') ? 'true' : 'false' }};
        document.getElementById("myForm").addEventListener("submit", function(event) {
            if (isOTPActive) {
                if ($(".phone-form-group").hasClass("d-none")) {
                    const emailInput = $("#email").val();
                    $("#finalInput").val(emailInput);
                } else if ($(".email-form-group").hasClass("d-none")) {
                    event.preventDefault();
                    let selectedDialCode = $(".iti__country.iti__active").data("dial-code") ?? '';
                    const phoneInput = $("#phone-code").val();
                    $("#finalInput").val("+" + selectedDialCode + phoneInput);
                }
            }
            this.submit();
        });
    </script>
    @if (addon_activation('otp_system'))
        @include('partials.emailOrPhone')
    @endif
@endsection