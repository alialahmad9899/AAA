@extends('admin.layouts.blank')

@section('content')
<style>
    /* خلفية تقنية مع طبقة حماية */
    .admin-login-page {
        background: linear-gradient(rgba(10, 25, 47, 0.8), rgba(10, 25, 47, 0.9)), 
                    url('{{ uploaded_asset(get_setting('admin_login_background')) }}');
        background-size: cover;
        background-position: center;
        min-height: 100vh;
        display: flex;
        align-items: center;
        font-family: 'Cairo', sans-serif;
    }

    /* صندوق الدخول الزجاجي "الخطير" */
    .glass-login-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        color: #fff;
        overflow: hidden;
        transition: 0.4s;
    }
    .glass-login-card:hover {
        border-color: #1877F2;
        box-shadow: 0 25px 50px rgba(24, 119, 242, 0.2);
    }

    /* أيقونة الدرع النابضة */
    .security-icon {
        font-size: 60px;
        color: #1877F2;
        margin-bottom: 20px;
        animation: pulse-blue 2s infinite;
    }
    @keyframes pulse-blue {
        0% { transform: scale(1); text-shadow: 0 0 0 rgba(24, 119, 242, 0.7); }
        70% { transform: scale(1.05); text-shadow: 0 0 20px rgba(24, 119, 242, 0); }
        100% { transform: scale(1); text-shadow: 0 0 0 rgba(24, 119, 242, 0); }
    }

    .form-control {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff !important;
        border-radius: 10px;
        padding: 12px 15px;
    }
    .form-control:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: #1877F2;
        box-shadow: none;
    }

    .btn-admin-login {
        background: #1877F2;
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: 0.3s;
    }
    .btn-admin-login:hover {
        background: #166fe5;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(24, 119, 242, 0.4);
    }

    .admin-brand-text {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.5px;
        background: linear-gradient(to right, #fff, #1877F2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* =========================================
       إضافات شاشة التحقق والخزنة الإلكترونية
       ========================================= */
    #secure-auth-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(10, 25, 47, 0.85); /* لون يتماشى مع خلفيتك */
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        z-index: 999999;
        display: none; /* مخفية افتراضياً */
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .safe-spinner {
        width: 100px;
        height: 100px;
        border: 5px dashed #1877F2;
        border-radius: 50%;
        animation: spinLock 3s linear infinite;
        position: relative;
        margin-bottom: 25px;
        transition: border-color 0.3s;
    }
    .safe-spinner::before {
        content: '';
        position: absolute;
        top: 10px; left: 10px; right: 10px; bottom: 10px;
        border: 3px dotted #fff;
        border-radius: 50%;
        animation: spinLock 2s linear infinite reverse;
    }
    .safe-spinner::after {
        content: '\f023'; /* أيقونة قفل */
        font-family: "Line Awesome Free";
        font-weight: 900;
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: 32px;
        color: #fff;
        transition: color 0.3s;
    }
    
    /* حالة النجاح للخزنة */
    .safe-spinner.success { border-color: #00ff88; }
    .safe-spinner.success::after { content: '\f058'; color: #00ff88; /* أيقونة صح */ }

    @keyframes spinLock { 100% { transform: rotate(360deg); } }

    #auth-status-text {
        font-size: 20px;
        font-weight: 700;
        color: #ffffff;
        transition: color 0.3s ease;
    }
</style>

{{-- شاشة التحقق الوهمية --}}
<div id="secure-auth-overlay">
    <div class="safe-spinner" id="spinner-icon"></div>
    <h3 id="auth-status-text" dir="rtl">جاري التحقق من بياناتك...</h3>
</div>

<div class="admin-login-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-5 col-xl-4 mx-auto">
                <div class="card glass-login-card">
                    <div class="card-body p-5">
                        {{-- أيقونة السيطرة العالمية --}}
                        <div class="text-center mb-4">
                            <div class="security-icon">
                                <i class="las la-shield-alt"></i>
                            </div>
                            <h1 class="admin-brand-text mb-2">النظام المركزي </h1>
                            <p class="text-muted small">بوابة الإدارة  | مصرح للمسؤولين فقط</p>
                        </div>

                        {{-- أضفنا ID للنموذج لربطه بكود التحقق (id="admin-secure-login-form") --}}
                        <form method="POST" action="{{ route('login') }}" id="admin-secure-login-form">
                            @csrf
                            <div class="form-group mb-4">
                                <label class="small fw-600">المعرف الرقمي للإدارة</label>
                                <div class="input-group">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required placeholder="البريد الإلكتروني المعتمد">
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="small fw-600">مفتاح الوصول السري</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required placeholder="كلمة المرور المشفرة">
                                @error('password')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="row mb-4">
                                <div class="col-6">
                                    <label class="aiz-checkbox">
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <span class="small text-muted">البقاء متصلاً</span>
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                                <div class="col-6 text-right">
                                    <a href="{{ route('password.request') }}" class="small text-primary">فقدت المفتاح؟</a>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-admin-login">
                                <i class="las la-fingerprint mr-2"></i> تأكيد الهوية والدخول
                            </button>
                        </form>

                        {{-- قسم الديمو يظهر فقط عند الضرورة بشكل أنيق --}}
                        @if (env("DEMO_MODE") == "On")
                            <div class="mt-4 p-3 border border-dashed border-secondary rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small">admin@example.com</span>
                                    <button class="btn btn-xs btn-outline-primary" onclick="autoFill()">نسخ البيانات</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                {{-- تذييل الصفحة --}}
                <div class="text-center mt-4">
                    <p class="text-muted small">© {{ date('Y') }} النظام للإدارة المركزية. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        function autoFill(){
            $('#email').val('admin@example.com');
            $('#password').val('12345678');
        }

        // كود تشغيل شاشة التحقق (مدة 4 ثواني)
        document.getElementById('admin-secure-login-form').addEventListener('submit', function(e) {
            e.preventDefault(); // منع الإرسال الفوري
            var form = this;
            var overlay = document.getElementById('secure-auth-overlay');
            var statusText = document.getElementById('auth-status-text');
            var spinner = document.getElementById('spinner-icon');

            // 1. إظهار الشاشة
            overlay.style.display = 'flex';
            statusText.innerText = 'جاري التحقق من بياناتك...';
            statusText.style.color = '#ffffff';
            spinner.classList.remove('success');

            // 2. بعد ثانيتين يتم تغيير الرسالة وشكل الخزنة
            setTimeout(function() {
                spinner.classList.add('success');
                        statusText.innerText = 'تم التحقق منك , سُمح لك بالدخول ';
                statusText.style.color = '#00ff88';

                // 3. بعد ثانيتين أخريين (المجموع 4 ثواني) يتم إرسال النموذج فعلياً
                setTimeout(function() {
                    form.submit();
                }, 2000);

            }, 2000);
        });
    </script>
@endsection