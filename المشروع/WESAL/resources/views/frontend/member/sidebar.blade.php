<style>
    /* 🛑 اللمسات الجمالية الملكية (ذهبي وكحلي) لمنصة وصال 🛑 */
    .aiz-user-sidenav {
        background: #ffffff;
        box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08); /* ظل أفخم */
        border: 1px solid rgba(0,0,0,0.03);
        border-radius: 20px;
        overflow: hidden; /* لمنع خروج العناصر عن الزوايا الدائرية */
    }
    
    /* رأس القائمة الملكي */
    .aiz-user-sidenav-header {
        background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); /* كحلي عميق */
        padding: 40px 15px 30px !important;
        margin-top: -15px; /* لدمجها مع حافة القائمة */
        margin-bottom: 25px !important;
        border-bottom-left-radius: 20px;
        border-bottom-right-radius: 20px;
        position: relative;
    }
    .aiz-user-sidenav-header .h5 {
        color: #D4AF37 !important; /* ذهبي */
        font-weight: 800;
        margin-top: 15px;
        font-size: 16px;
        letter-spacing: 0.5px;
    }
    .aiz-user-sidenav-header .avatar img {
        border: 3px solid #D4AF37;
        box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        background: #fff;
        transition: transform 0.3s ease;
    }
    .aiz-user-sidenav-header .avatar img:hover {
        transform: scale(1.05); /* تكبير خفيف للصورة عند تمرير الماوس */
    }

    /* تنسيق الروابط وتأثير المرور */
    .aiz-side-nav-list {
        direction: rtl;
        text-align: right;
    }
    .aiz-side-nav-link {
        color: #475569 !important; /* رمادي داكن مريح */
        font-weight: 600 !important;
        padding: 12px 15px !important;
        border-radius: 12px;
        margin: 0 15px 6px 15px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 14.5px;
    }
    .aiz-side-nav-link:hover, 
    .aiz-side-nav-link.active {
        background: var(--soft-primary) !important;
        color: var(--secondary) !important;
        transform: translateX(-6px); /* حركة خفيفة ناعمة لليسار */
        font-weight: 700 !important;
    }
    .aiz-side-nav-icon {
        font-size: 22px !important;
        margin-left: 12px !important;
        margin-right: 0 !important;
        color: #D4AF37 !important;
        transition: transform 0.3s ease;
    }
    .aiz-side-nav-link:hover .aiz-side-nav-icon,
    .aiz-side-nav-link.active .aiz-side-nav-icon {
        transform: scale(1.15); /* تكبير الأيقونة قليلاً عند التفعيل */
    }
    .aiz-side-nav-arrow {
        margin-right: auto !important;
        margin-left: 0 !important;
    }

    /* القوائم الفرعية */
    .level-2 .aiz-side-nav-link {
        padding: 10px 30px 10px 15px !important;
        font-size: 13.5px;
        color: #64748b !important;
    }

    /* أزرار الإجراءات */
    .btn-public-profile {
        background-color: var(--soft-primary) !important;
        color: var(--primary) !important;
        font-weight: 800;
        border-radius: 12px;
        transition: all 0.3s ease;
        font-size: 14px;
        padding: 12px;
        border: 1px solid transparent;
    }
    .btn-public-profile:hover {
        background-color: var(--primary) !important;
        color: #ffffff !important;
        box-shadow: 0 6px 20px rgba(212, 175, 55, 0.3);
    }
    
    .btn-logout-custom {
        background: #fff5f5 !important;
        color: #ef4444 !important;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.3s ease;
        padding: 12px;
        font-size: 14.5px;
        border: 1px solid #fee2e2;
    }
    .btn-logout-custom:hover {
        background: #ef4444 !important;
        color: #ffffff !important;
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
    }

    /* 🛑 تنعيم وتصغير السايدبار على الموبايل 🛑 */
    @media (max-width: 991px) {
        .aiz-mobile-side-nav .collapse-sidebar {
            width: 280px !important; /* عرض مثالي للموبايل */
            max-width: 85vw !important;
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
            background: #ffffff;
        }
        .aiz-user-sidenav-wrap {
            padding-top: 0 !important; 
        }
        .absolute-top-left {
            top: 15px !important;
            left: 15px !important;
            z-index: 10;
        }
        .absolute-top-left .btn {
            background: rgba(255,255,255,0.2) !important;
            backdrop-filter: blur(5px);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .absolute-top-left .btn i {
            color: #ffffff !important; 
            font-size: 18px;
        }
        .aiz-side-nav-link {
            padding: 10px 12px !important; 
            font-size: 14px;
            margin: 0 10px 4px 10px;
        }
        .aiz-side-nav-icon {
            font-size: 20px !important;
        }
    }
</style>

<div class="aiz-user-sidenav-wrap pt-4 sticky-top c-scrollbar-light position-relative z-1 shadow-none">
    
    {{-- زر الإغلاق للموبايل --}}
    <div class="absolute-top-left d-xl-none">
        <button class="btn btn-sm p-0 shadow-none" data-toggle="class-toggle" data-target=".aiz-mobile-side-nav" data-same=".mobile-side-nav-thumb">
            <i class="las la-times"></i>
        </button>
    </div>
    
    <div class="aiz-user-sidenav rounded overflow-hidden">
        {{-- رأس القائمة المطور --}}
        <div class="px-4 text-center mb-4 aiz-user-sidenav-header">
            <span class="avatar avatar-md mb-3">
                @if (Auth::user()->photo != null)
                    <img src="{{ uploaded_asset(Auth::user()->photo) }}" style="object-fit: cover;">
                @else
                    <img src="{{ static_asset('assets/img/avatar-place.png') }}">
                @endif
            </span>
            <h4 class="h5 fw-600">{{ Auth::user()->first_name . ' ' . Auth::user()->last_name }}</h4>
        </div>
        
        <div class="text-center mb-4 px-3">
            <a href="{{ route('member_profile', Auth::user()->id) }}" class="btn btn-block btn-public-profile">
                <i class="las la-eye fs-18 mr-1 align-middle"></i> رؤية ملفي العام
            </a>
        </div>

        <div class="sidemnenu mb-3">
            <ul class="aiz-side-nav-list" data-toggle="aiz-side-menu">

                {{-- الخيار 1: لوحة التحكم --}}
                <li class="aiz-side-nav-item">
                    <a href="{{ route('dashboard') }}" class="aiz-side-nav-link {{ areActiveRoutes(['dashboard']) }}">
                        <i class="las la-home aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">لوحة التحكم</span>
                    </a>
                </li>
                
                {{-- الخيار 2: إدارة الملف الشخصي --}}
                <li class="aiz-side-nav-item">
                    <a href="{{ route('profile_settings') }}" class="aiz-side-nav-link">
                        <i class="las la-user-edit aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">إدارة الملف الشخصي</span>
                    </a>
                </li>

                {{-- الخيار 3: المعرض --}}
                <li class="aiz-side-nav-item">
                    <a href="{{ route('gallery-image.index') }}" class="aiz-side-nav-link">
                        <i class="las la-images aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">معرض صوري</span>
                    </a>
                </li>

                {{-- قسم الباقات --}}
                <li class="aiz-side-nav-item">
                    <a href="javascript:void(0);" class="aiz-side-nav-link">
                        <i class="las la-gem aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">باقات الاشتراك</span>
                        <span class="aiz-side-nav-arrow"></span>
                    </a>
                    <ul class="aiz-side-nav-list level-2">
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('packages') }}" class="aiz-side-nav-link">
                                <span class="aiz-side-nav-text">عرض الباقات</span>
                            </a>
                        </li>
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('package_purchase_history') }}" class="aiz-side-nav-link">
                                <span class="aiz-side-nav-text">سجل مشترياتي</span>
                            </a>
                        </li>
                    </ul>
                </li>

                @if (get_setting('wallet_system'))
                    <li class="aiz-side-nav-item">
                        <a href="{{ route('wallet.index') }}" class="aiz-side-nav-link">
                            <i class="las la-wallet aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">محفظتي المالية</span>
                        </a>
                    </li>
                @endif

                @if (addon_activation('referral_system'))
                    <li class="aiz-side-nav-item">
                        <a href="javascript:void(0);" class="aiz-side-nav-link">
                            <i class="las la-bullhorn aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">نظام الدعوات والأرباح</span>
                            @if (env('DEMO_MODE') == 'On')
                                <span class="badge badge-inline badge-danger">إضافة</span>
                            @endif
                            <span class="aiz-side-nav-arrow"></span>
                        </a>
                        <ul class="aiz-side-nav-list level-2">
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('my_referred_users') }}" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">الأصدقاء المدعوين</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('my_referral_earnings') }}" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">أرباحي من الدعوات</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item">
                                <a href="{{ route('wallet_withdraw_request_history') }}" class="aiz-side-nav-link">
                                    <span class="aiz-side-nav-text">طلبات سحب الأرباح</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- الرسائل --}}
                <li class="aiz-side-nav-item">
                    <a href="{{ route('all.messages') }}" class="aiz-side-nav-link">
                        <i class="las la-comments aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">الرسائل والمحادثات</span>
                    </a>
                </li>

                @if (addon_activation('support_tickets'))
                    <li class="aiz-side-nav-item">
                        <a href="{{ route('support-tickets.user_index') }}" class="aiz-side-nav-link {{ areActiveRoutes(['support-tickets.user_index', 'support-tickets.user_ticket_create']) }}">
                            <i class="las la-life-ring aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">تذاكر الدعم الفني</span>
                            @if (env('DEMO_MODE') == 'On')
                                <span class="badge badge-inline badge-danger">إضافة</span>
                            @endif
                        </a>
                    </li>
                @endif

                {{-- الاهتمامات --}}
                <li class="aiz-side-nav-item">
                    <a href="{{ route('my_interests.index') }}" class="aiz-side-nav-link">
                        <i class="las la-heart aiz-side-nav-icon text-danger" style="color: #ef4444 !important;"></i>
                        <span class="aiz-side-nav-text">الإعجابات والاهتمامات</span>
                    </a>
                </li>

                @if (get_setting('profile_picture_privacy') == 'only_me' || get_setting('gallery_image_privacy') == 'only_me')
                    <li class="aiz-side-nav-item">
                        <a href="javascript:void(0);" class="aiz-side-nav-link">
                            <i class="las la-camera aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">طلبات رؤية صوري</span>
                            <span class="aiz-side-nav-arrow"></span>
                        </a>
                        <ul class="aiz-side-nav-list level-2">
                            @if (get_setting('profile_picture_privacy') == 'only_me')
                                <li class="aiz-side-nav-item">
                                    <a href="{{ route('profile-picture-view-request.index') }}" class="aiz-side-nav-link">
                                        <span class="aiz-side-nav-text">طلبات الصورة الشخصية</span>
                                    </a>
                                </li>
                            @endif
                            @if (get_setting('gallery_image_privacy') == 'only_me')
                                <li class="aiz-side-nav-item">
                                    <a href="{{ route('gallery-image-view-request.index') }}" class="aiz-side-nav-link">
                                        <span class="aiz-side-nav-text">طلبات صور المعرض</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- القائمة المفضلة والحظر --}}
                <li class="aiz-side-nav-item mt-2">
                    <a href="{{ route('my_shortlists') }}" class="aiz-side-nav-link">
                        <i class="las la-list-alt aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">القائمة المفضلة</span>
                    </a>
                </li>
                <li class="aiz-side-nav-item">
                    <a href="{{ route('my_ignored_list') }}" class="aiz-side-nav-link">
                        <i class="las la-user-slash aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">قائمة التجاهل (الحظر)</span>
                    </a>
                </li>

                {{-- الإعدادات السفلية --}}
                <li class="aiz-side-nav-item mt-3 pt-2" style="border-top: 1px dashed rgba(0,0,0,0.05);">
                    <a href="{{ route('member.change_password') }}" class="aiz-side-nav-link">
                        <i class="las la-key aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">تغيير كلمة المرور</span>
                    </a>
                </li>
                
                <li class="aiz-side-nav-item">
                    <a href="javascript:void(0);" class="aiz-side-nav-link" onclick="account_deactivation()">
                        @if (Auth::user()->deactivated == 0)
                            <i class="las la-user-lock aiz-side-nav-icon text-warning" style="color: #f59e0b !important;"></i>
                            <span class="aiz-side-nav-text">إيقاف الحساب مؤقتاً</span>
                        @else
                            <i class="las la-user-check aiz-side-nav-icon text-success" style="color: #10b981 !important;"></i>
                            <span class="aiz-side-nav-text">إعادة تنشيط الحساب</span>
                        @endif
                    </a>
                </li>
                
                <li class="aiz-side-nav-item">
                    <a href="javascript:void(0);" class="aiz-side-nav-link" onclick="account_delete()">
                        <i class="las la-trash-alt aiz-side-nav-icon text-danger" style="color: #ef4444 !important;"></i>
                        <span class="aiz-side-nav-text">حذف الحساب نهائياً</span>
                    </a>
                </li>
            </ul>
        </div>
        
        {{-- زر تسجيل الخروج بمظهر جديد --}}
        <div class="mb-4 px-4">
            <a href="javascript:void(0);" class="btn btn-block btn-logout-custom" 
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="las la-sign-out-alt fs-20 align-middle mr-2"></i>
                <span>تسجيل الخروج</span>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </a>
        </div>
    </div>
</div>