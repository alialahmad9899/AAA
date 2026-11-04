@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* التنسيقات الفاخرة للوحة التحكم (Royal Theme) */
    .dashboard-wrapper { font-family: inherit; }
    
    .luxury-stat-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 15px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(212, 175, 55, 0.1);
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .luxury-stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: linear-gradient(180deg, var(--primary), var(--hov-primary));
        border-radius: 4px 0 0 4px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .luxury-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(212, 175, 55, 0.12);
        border-color: rgba(212, 175, 55, 0.3);
    }
    .luxury-stat-card:hover::before { opacity: 1; }

    .luxury-stat-card .icon-wrap {
        width: 50px; height: 50px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; margin-left: 15px;
        flex-shrink: 0;
    }
    .stat-details { flex-grow: 1; }
    .stat-details .stat-value { font-size: 24px; font-weight: 900; color: var(--secondary); line-height: 1.2; }
    .stat-details .stat-label { font-size: 13px; color: #64748b; font-weight: 700; margin-top: 4px; }
    
    /* ألوان الأيقونات الملكية المتناسقة */
    .bg-royal-gold { background: rgba(212, 175, 55, 0.1); color: var(--primary); border: 1px solid rgba(212, 175, 55, 0.2); }
    .bg-royal-navy { background: rgba(15, 23, 42, 0.05); color: var(--secondary); border: 1px solid rgba(15, 23, 42, 0.1); }
    .bg-royal-green { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    .bg-royal-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

    .dashboard-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 5px 25px rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(15, 23, 42, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
        height: 100%;
        transition: 0.3s;
    }
    .dashboard-card:hover { box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08); }
    .dashboard-card-header {
        background: #f8fafc;
        padding: 20px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.05);
        font-weight: 900;
        font-size: 16px;
        color: var(--secondary);
    }

    /* === تنسيقات بطاقة الباقة الفاخرة === */
    .luxury-package-card {
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(212, 175, 55, 0.2);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        transition: all 0.4s ease;
        position: relative;
        margin-bottom: 20px;
    }
    .luxury-package-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(212, 175, 55, 0.15);
        border-color: var(--primary);
    }
    .package-img-header {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-bottom: 4px solid var(--primary);
        transition: transform 0.5s;
    }
    .luxury-package-card:hover .package-img-header { transform: scale(1.05); }

    .package-name-badge {
        position: absolute;
        top: 140px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, var(--secondary), #1e293b);
        color: var(--primary);
        padding: 8px 30px;
        border-radius: 30px;
        font-weight: 900;
        font-size: 16px;
        box-shadow: 0 5px 15px rgba(15, 23, 42, 0.3);
        white-space: nowrap;
        z-index: 2;
        border: 1px solid rgba(212, 175, 55, 0.3);
    }
    .package-features-list {
        list-style: none;
        padding: 0;
        margin: 40px 0 15px 0;
    }
    .package-features-list li {
        padding: 12px 20px;
        border-bottom: 1px dashed rgba(15, 23, 42, 0.05);
        font-size: 13.5px;
        font-weight: 700;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .package-features-list li:last-child { border-bottom: none; }
    .feature-title { display: flex; align-items: center; gap: 8px; color: var(--secondary); }
    .package-features-list li i.la-check-circle { color: #10b981; font-size: 22px; }
    .package-features-list li i.la-times-circle { color: #ef4444; font-size: 22px; }
    .quota-bold { font-weight: 900; color: var(--primary); }
    
    .package-price-section {
        background: #f8fafc;
        padding: 25px 20px;
        text-align: center;
        border-radius: 0 0 20px 20px;
        border-top: 1px solid rgba(15, 23, 42, 0.05);
    }
    .price-amount {
        font-size: 1.8rem;
        font-weight: 900;
        color: var(--secondary);
        line-height: 1;
        text-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .price-duration {
        font-size: 13px;
        color: #64748b;
        font-weight: 800;
        margin-bottom: 15px;
    }
    .btn-buy-package {
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        color: #ffffff !important;
        font-weight: 900;
        border-radius: 30px;
        padding: 14px 30px;
        border: none;
        width: 100%;
        transition: all 0.3s;
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        display: inline-block;
        text-decoration: none;
        font-size: 15px;
    }
    .btn-buy-package:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(212, 175, 55, 0.4);
    }

    /* === تنسيقات النافذة الترحيبية الجديدة (Royal Glassmorphism) === */
    @keyframes textShineGold {
        0% { color: var(--secondary); text-shadow: 0 0 5px rgba(212, 175, 55, 0); }
        50% { color: var(--primary); text-shadow: 0 0 15px rgba(212, 175, 55, 0.6); }
        100% { color: var(--secondary); text-shadow: 0 0 5px rgba(212, 175, 55, 0); }
    }
    .welcome-glass-modal .modal-content {
        background: rgba(255, 255, 255, 0.9) !important;
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-radius: 25px;
        border: 1px solid rgba(212, 175, 55, 0.4);
        box-shadow: 0 25px 50px rgba(15, 23, 42, 0.2);
    }
    .welcome-shiny-title { animation: textShineGold 3s infinite ease-in-out; font-weight: 900; }
    .glass-btn-profile {
        background: linear-gradient(135deg, var(--primary), var(--hov-primary)) !important;
        color: white !important;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        transition: 0.3s;
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
    }
    .glass-btn-profile:hover { transform: scale(1.05); box-shadow: 0 12px 25px rgba(212, 175, 55, 0.4); }
    .royal-strip-black { background: var(--secondary); color: var(--primary); padding: 6px 20px; border-radius: 10px; display: inline-block; margin-bottom: 15px; font-weight: 900; letter-spacing: 0.5px;}

    @media (max-width: 767px) {
        .luxury-stat-card { padding: 15px 12px; }
        .luxury-stat-card .icon-wrap { margin-left: 10px; width: 45px; height: 45px; font-size: 24px; }
        .stat-details .stat-value { font-size: 20px; }
        .stat-details .stat-label { font-size: 11px; }
        .col-mobile-6 { padding-right: 8px; padding-left: 8px; }
        .package-img-header { height: 130px; }
        .package-name-badge { top: 110px; font-size: 14px; padding: 6px 20px; }
    }
</style>

<div class="dashboard-wrapper text-right" dir="rtl">
    
    @php
        $user = Auth::user();
        $package = $user->member->package;
        $profile_picture_privacy = get_setting('profile_picture_privacy');
        $gallery_image_privacy = get_setting('gallery_image_privacy');

        $message_quota = $package->message_profile_quota ?? -1;
        $used_messages = \App\Models\MessageQuotaUsage::where('user_id', $user->id)->count();
        $remaining_messages = ($message_quota == -1) ? 'غير محدود' : max(0, $message_quota - $used_messages);
        
        $is_profile_finished = (!empty($user->member->introduction) && $user->partner_expectations != null) ? true : false;
    @endphp

    {{-- قسم الإحصائيات العلوية --}}
    <div class="row mb-4">
        <div class="col-6 col-md-4 col-xl-3 mb-3 col-mobile-6">
            <div class="luxury-stat-card">
                <div class="icon-wrap bg-royal-navy"><i class="las la-envelope"></i></div>
                <div class="stat-details">
                    <div class="stat-value">{{ $remaining_messages }}</div>
                    <div class="stat-label">محادثات متبقية</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-3 mb-3 col-mobile-6">
            <div class="luxury-stat-card">
                <div class="icon-wrap bg-royal-red"><i class="las la-heart"></i></div>
                <div class="stat-details">
                    <div class="stat-value">{{ get_remaining_package_value($user->id,'remaining_interest') }}</div>
                    <div class="stat-label">إعجابات متبقية</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-3 mb-3 col-mobile-6">
            <div class="luxury-stat-card">
                <div class="icon-wrap bg-royal-gold"><i class="las la-phone-volume"></i></div>
                <div class="stat-details">
                    <div class="stat-value">{{ get_remaining_package_value($user->id,'remaining_contact_view') }}</div>
                    <div class="stat-label">كشف أرقام متبقي</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-3 mb-3 col-mobile-6">
            <div class="luxury-stat-card">
                <div class="icon-wrap bg-royal-green"><i class="las la-eye"></i></div>
                <div class="stat-details">
                    <div class="stat-value">{{ get_remaining_package_value($user->id,'remaining_profile_viewer_view') }}</div>
                    <div class="stat-label">معرفة الزوار</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-3 mb-3 col-mobile-6">
            <div class="luxury-stat-card">
                <div class="icon-wrap bg-royal-navy"><i class="las la-images"></i></div>
                <div class="stat-details">
                    <div class="stat-value">{{ get_remaining_package_value($user->id,'remaining_photo_gallery') }}</div>
                    <div class="stat-label">صور معرض متبقية</div>
                </div>
            </div>
        </div>

        @if($profile_picture_privacy == 'only_me')
        <div class="col-6 col-md-4 col-xl-3 mb-3 col-mobile-6">
            <div class="luxury-stat-card">
                <div class="icon-wrap bg-royal-gold"><i class="las la-user-lock"></i></div>
                <div class="stat-details">
                    <div class="stat-value">{{ get_remaining_package_value($user->id,'remaining_profile_image_view') }}</div>
                    <div class="stat-label">كشف صور شخصية</div>
                </div>
            </div>
        </div>
        @endif

        @if($gallery_image_privacy == 'only_me')
        <div class="col-6 col-md-4 col-xl-3 mb-3 col-mobile-6">
            <div class="luxury-stat-card">
                <div class="icon-wrap bg-royal-gold"><i class="las la-folder-open"></i></div>
                <div class="stat-details">
                    <div class="stat-value">{{ get_remaining_package_value($user->id,'remaining_gallery_image_view') }}</div>
                    <div class="stat-label">كشف صور المعرض</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- القسم السفلي: الباقة الحالية والمطابقات --}}
    <div class="row">
        <div class="col-lg-6 mb-4">
            <h5 class="fw-900 text-dark mb-3"><i class="las la-crown mr-1" style="color: var(--primary);"></i> باقتك الحالية</h5>
            <div class="luxury-package-card text-right">
                <img class="package-img-header" src="{{ uploaded_asset($package->image) }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                <div class="package-name-badge">{{$package->name}}</div>
                <ul class="package-features-list">
                    <li style="background: rgba(212, 175, 55, 0.05); border-radius: 12px; margin: 0 15px;">
                        <span class="feature-title fw-800" style="color: var(--primary);"><i class="las la-envelope fs-22"></i> المحادثات المسموحة:</span>
                        @if($package->message_profile_quota == -1)
                            <span class="badge badge-inline px-3 py-1 fs-12 fw-800" style="background: var(--primary); color: #fff;">غير محدود</span>
                        @else
                            <span class="badge badge-inline px-3 py-1 fs-12 fw-800" style="background: var(--secondary); color: #fff;">{{ $package->message_profile_quota }} أشخاص</span>
                        @endif
                    </li>
                    <li><span class="feature-title"><i class="las la-heart text-danger fs-22"></i> إرسال إعجاب واهتمام:</span><span class="quota-bold">{{ $package->express_interest }} مرة</span></li>
                    <li><span class="feature-title"><i class="las la-images text-info fs-22"></i> رفع صور في المعرض:</span><span class="quota-bold">{{ $package->photo_gallery }} صور</span></li>
                    <li><span class="feature-title"><i class="las la-phone-volume fs-22" style="color: var(--primary);"></i> كشف رقم التواصل:</span><span class="quota-bold">{{ $package->contact }} مرات</span></li>
                    <li><span class="feature-title"><i class="las la-eye text-success fs-22"></i> معرفة من زار ملفك:</span><span class="quota-bold">{{ $package->profile_viewers_view }} مرات</span></li>
                    <li><span class="feature-title"><i class="las la-robot fs-22" style="color: var(--secondary);"></i> نظام التطابق الذكي:</span> @if( $package->auto_profile_match == 0 ) <i class="las la-times-circle"></i> @else <i class="las la-check-circle"></i> @endif </li>
                </ul>
                <div class="package-price-section">
                    <div class="price-duration">تاريخ انتهاء باقتك الحالية:</div>
                    <div class="mb-4">
                        @if(\App\Utility\MemberUtility::is_female($user))
                            <div class="price-amount text-success">مجاني دائماً</div>
                        @elseif(package_validity($user->id))
                            <div class="price-amount text-success">{{ date('Y/m/d', strtotime($user->member->package_validity)) }}</div>
                        @else
                            <div class="price-amount text-danger">منتهية الصلاحية</div>
                        @endif
                    </div>
                    <a href="{{ route('packages') }}" class="btn-buy-package text-center"><i class="las la-arrow-up mr-1"></i> ترقية أو تجديد الباقة الآن</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            @if(get_setting('member_verification'))
                <div class="dashboard-card mb-4 p-4 text-center">
                    @if ($user->approved == 0)
                        <img src="{{ static_asset('assets/img/non_verified.png') }}" alt="غير موثق" class="w-90px mx-auto mb-3 d-block opacity-60">
                        <h6 class="fw-800 text-dark mb-3">حسابك غير موثق بعد</h6>
                        <a href="{{ route('member.verification') }}" class="btn btn-sm text-white rounded-pill px-4 fw-800 shadow-sm" style="background: linear-gradient(135deg, var(--secondary), #1e293b);">وثق حسابك الآن</a>
                    @else
                        <img src="{{ static_asset('assets/img/verified.png') }}" alt="موثق" class="w-90px mx-auto mb-2 d-block" style="filter: drop-shadow(0 4px 6px rgba(16, 185, 129, 0.3));">
                        <h6 class="fw-900 text-success mb-0">حساب موثق رسمياً</h6>
                    @endif
                </div>
            @endif
            <div class="dashboard-card">
                <div class="dashboard-card-header d-flex justify-content-between align-items-center">
                    <span><i class="las la-user-friends mr-1 fs-20" style="color: var(--primary);"></i> تطابقات مقترحة لك</span>
                </div>
                <div class="card-body p-4">
                    @if(\App\Utility\MemberUtility::can_auto_match(Auth::user()->id))
                        @forelse ($similar_profiles->shuffle()->take(5) as $similar_profile)
                          @if($similar_profile->user != null)
                            <a href="{{ route('member_profile', $similar_profile->match_id) }}" class="text-reset d-block mb-3 p-3 bg-light rounded-lg border border-light" style="transition: 0.3s; border: 1px solid rgba(15, 23, 42, 0.05) !important;">
                                <div class="row align-items-center no-gutters">
                                    <div class="col-auto">
                                      @php
                                          $avatar_image = $similar_profile->user->member->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                          $profile_picture_show = show_profile_picture($similar_profile->user);
                                      @endphp
                                      <img @if ($profile_picture_show) src="{{ uploaded_asset($similar_profile->user->photo) }}" @else src="{{ static_asset($avatar_image) }}" @endif class="img-fit size-60px rounded-circle shadow-sm" style="border: 2px solid var(--primary);">
                                    </div>
                                    <div class="col pr-3">
                                        <h6 class="fs-15 fw-900 mb-1 text-truncate" style="color: var(--secondary);">{{ $similar_profile->user->first_name.' '.$similar_profile->user->last_name }}</h6>
                                        <div class="fs-13 fw-600 text-muted text-truncate">
                                            @if(!empty($similar_profile->user->member->birthday)) {{ \Carbon\Carbon::parse($similar_profile->user->member->birthday)->age }} سنة، @endif
                                            @if(!empty($similar_profile->user->member->marital_status->name)) {{ $similar_profile->user->member->marital_status->name }}، @endif
                                            {{ !empty($similar_profile->user->spiritual_backgrounds->religion->name) ? $similar_profile->user->spiritual_backgrounds->religion->name : "" }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                          @endif
                        @empty
                            <div class="text-center p-4"><p class="text-muted fs-14 fw-600 mb-0">يرجى تحديث مواصفات شريك حياتك للحصول على تطابقات ذكية.</p></div>
                        @endforelse
                    @else
                        <div class="text-center p-5">
                            <i class="las la-robot la-4x opacity-30 mb-3 d-block" style="color: var(--secondary);"></i>
                            <p class="text-dark fs-15 fw-800 mb-4">خاصية التطابق الذكي غير متاحة في باقتك الحالية.</p>
                            <a href="{{ route('packages') }}" class="btn btn-outline-primary rounded-pill px-4 fw-800" style="border-width: 2px;">قم بالترقية الآن</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- === النافذة الترحيبية الملكية (لا تظهر إذا كان الملف مكتمل) === --}}
@if(!$is_profile_finished)
<div class="modal fade welcome-glass-modal" id="welcomeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0">
            <div class="modal-body p-5 text-center">
                <div class="royal-strip-black">أهلاً بك في منصة وصال</div>
                <h2 class="welcome-shiny-title mb-4">خطوة واحدة تفصلك عن شريك العمر!</h2>
                <div class="mb-4">
                    <i class="las la-gem la-4x mb-3 animate__animated animate__pulse animate__infinite" style="color: var(--primary);"></i>
                    <p class="text-dark fw-700 fs-16" style="line-height: 1.8;">
                        هل تعلم أن الملفات الشخصية <span class="fw-900" style="color: var(--primary);">المكتملة</span> تحصل على فرص تفاعل أكبر بنسبة <span class="text-success fw-900">80%</span>؟<br>
                        ننصحك بإكمال بياناتك الآن لتجد نصفك الآخر بسرعة وسهولة.
                    </p>
                </div>
                <div class="row">
                    <div class="col-12 mb-3">
                        <a href="{{ route('profile_settings') }}" class="btn btn-block glass-btn-profile py-3 fs-16">
                            <i class="las la-user-edit mr-1 fs-20"></i> أكمل ملفي الشخصي الآن
                        </a>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-link text-muted fw-800 fs-14 text-decoration-none" onclick="dismissWelcomeModal()">سأقوم بذلك لاحقاً</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('script')
    <script>
        // إظهار النافذة الترحيبية تلقائياً إذا لم يكن الملف مكتملاً ولم يضغط العضو على "لاحقاً"
        @if(!$is_profile_finished)
        $(document).ready(function() {
            if(localStorage.getItem('welcome_modal_hidden') !== 'true') {
                setTimeout(function() {
                    $('#welcomeModal').modal('show');
                }, 1000); 
            }
        });

        // دالة إخفاء النافذة وحفظ الخيار في المتصفح
        function dismissWelcomeModal() {
            localStorage.setItem('welcome_modal_hidden', 'true');
            $('#welcomeModal').modal('hide');
        }
        @endif

        var isOTPActive = {{ addon_activation('otp_system') ? 'true' : 'false' }};
        const daySelect = document.getElementById('dob_day');
        const monthSelect = document.getElementById('dob_month');
        const yearSelect = document.getElementById('dob_year');
        const hiddenDobInput = document.getElementById('date_of_birth');
        if(daySelect){
            function combineDate() {
                if(daySelect.value && monthSelect.value && yearSelect.value) {
                    hiddenDobInput.value = yearSelect.value + '-' + monthSelect.value + '-' + daySelect.value;
                }
            }
            daySelect.addEventListener('change', combineDate);
            monthSelect.addEventListener('change', combineDate);
            yearSelect.addEventListener('change', combineDate);
        }
    </script>
    @if (addon_activation('otp_system'))
        @include('partials.emailOrPhone')
    @endif
@endsection