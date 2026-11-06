<style>
    /* --- لمسات الفخامة الخاصة بالهيدر --- */
    .top-navbar-premium {
        background-color: var(--secondary) !important;
        border-bottom: 1px solid rgba(212, 175, 55, 0.2) !important;
    }
    .top-navbar-premium .text-reset, 
    .top-navbar-premium .opacity-60 {
        color: #e2e8f0 !important;
        opacity: 0.9 !important;
        transition: all 0.3s ease;
    }
    .top-navbar-premium .text-reset:hover,
    .top-navbar-premium .dropdown-toggle:hover i {
        color: var(--primary) !important;
        opacity: 1 !important;
    }

    .glass-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    .pulse-badge {
        animation: pulse-gold 2s infinite;
        position: absolute;
        top: 0px;
        right: 0px;
        border: 2px solid #fff;
    }
    @keyframes pulse-gold {
        0% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(212, 175, 55, 0); }
        100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
    }

    .premium-nav-link {
        position: relative;
        transition: all 0.3s ease;
        background-color: transparent !important;
    }
    .premium-nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 2px;
        background: linear-gradient(45deg, #d4af37, #b5952f);
        transition: width 0.3s ease;
    }
    .premium-nav-link:hover::after,
    .premium-nav-link.active::after { width: 80%; }

    /* 🛑 القائمة الجانبية للموبايل (مقاس أنيق وملموم) 🛑 */
    .mobile-side-menu {
        position: fixed;
        top: 0;
        right: -300px;
        width: 275px; /* تم تصغير العرض ليكون أنيقاً */
        max-width: 85vw; /* حماية للشاشات الصغيرة جداً */
        height: 100vh;
        background: #ffffff;
        z-index: 1060;
        transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -5px 0 25px rgba(0,0,0,0.15);
        overflow-y: auto;
    }
    .mobile-side-menu.show { right: 0; }
    
    .mobile-menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(3px);
        z-index: 1050;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .mobile-menu-overlay.show { opacity: 1; visibility: visible; }

    .mobile-menu-header {
        background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%);
        padding: 40px 20px 25px; /* زيادة التناسق العلوي */
        color: #fff;
        position: relative;
        border-bottom-left-radius: 25px;
        border-bottom-right-radius: 25px;
    }
    .close-mobile-menu {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
    }

    .mobile-nav-item {
        padding: 12px 20px;
        border-bottom: 1px solid rgba(0,0,0,0.03);
        color: #334155;
        font-weight: 700;
        display: flex;
        align-items: center;
        font-size: 14.5px;
    }
    .mobile-nav-item:active { background: rgba(212, 175, 55, 0.05); }
    .mobile-nav-icon { color: #D4AF37; font-size: 22px; margin-left: 12px; width: 24px; text-align: center; }

    /* عنوان القسم في القائمة */
    .mobile-nav-heading {
        padding: 15px 20px 8px;
        font-size: 12px;
        font-weight: 800;
        color: var(--secondary);
        text-transform: uppercase;
        border-right: 3px solid var(--primary);
        margin-top: 10px;
        background: #f8fafc;
    }

    /* 🛑 زر الموبايل الصريح (بدون خلفية زهرية) 🛑 */
    .btn-mobile-explicit {
        border: 2px solid var(--primary); /* إطار ذهبي سميك وواضح */
        color: var(--secondary); /* نص كحلي */
        background: #ffffff; /* خلفية بيضاء نقية */
        padding: 5px 14px;
        font-size: 14px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(212, 175, 55, 0.15); /* ظل ذهبي فخم */
        transition: 0.3s ease;
    }
    .btn-mobile-explicit:active {
        transform: scale(0.96);
    }
</style>

<div class="@if(get_setting('header_stikcy') == 'on') position-fixed @else position-absolute @endif w-100 top-0 z-1020">
    <div class="top-navbar top-navbar-premium z-1035 py-2 d-none d-lg-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 col">
                    <ul class="list-inline d-flex justify-content-between justify-content-lg-start mb-0">
                        <li class="list-inline-item">
                          <a href="{{ get_setting('header_left_quick_link1') }}" class="text-reset opacity-60">
                            <span>{{ get_setting('header_left_quick_link1_text') }}</span>
                          </a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-7 col">
                    <ul class="list-inline mb-0 d-flex align-items-center justify-content-end ">
                        <li class="list-inline-item mr-3 pr-3 border-right text-reset opacity-60" style="border-color: rgba(255,255,255,0.1) !important;">
                            <span>{{ translate(' Help Line') }}</span>
                            <span>{{ get_setting('header_helpline_no') }}</span>
                        </li>
                        @if (Auth::check())
                            <li class="list-inline-item dropdown">
                                @php
                                $notifications = \App\Models\Notification::latest()->where('notifiable_id',Auth()->user()->id)->take(10)->get();
                                $unseen_notification = \App\Models\Notification::where('notifiable_id',Auth()->user()->id)->where('read_at',null)->count();
                                @endphp
                                <a href="javascript:void(0)" class="dropdown-toggle text-reset no-arrow position-relative d-inline-block py-1" data-toggle="dropdown" data-display="static">
                                    <i class="las la-bell fs-20 opacity-80"></i>
                                    @if($unseen_notification > 0)
                                        <span class="badge badge-dot badge-sm badge-status no-border badge-primary pulse-badge"></span>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg py-0 border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                                    <div class="p-3 bg-light border-bottom">
                                        <h6 class="mb-0 fw-700">{{ translate('Notifications') }}</h6>
                                    </div>
                                    <ul class="list-group list-group-raw c-scrollbar-light" style="overflow-y:auto;max-height:300px;">
                                        @include('frontend.inc.notification')
                                    </ul>
                                    <div class="border-top">
                                        <a href="{{ route('frontend.notifications') }}" class="btn text-reset btn-block fw-600 bg-light">{{ translate('View All Notifications') }}</a>
                                    </div>
                                </div>
                            </li>
                            
                            @php
                            $unseen_chat_threads = chat_threads();
                            $unseen_chat_thread_count = count($unseen_chat_threads);
                            @endphp
                            
                            <li class="list-inline-item dropdown">
                                <a href="javascript:void(0)" class="dropdown-toggle text-reset no-arrow position-relative d-inline-block py-1 mx-3" data-toggle="dropdown" data-display="static">
                                    <i class="las la-envelope fs-20 opacity-80"></i>
                                    @if($unseen_chat_thread_count > 0)
                                        <span class="badge badge-dot badge-sm badge-status no-border badge-primary pulse-badge"></span>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg py-0 border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                                    <div class="p-3 bg-light border-bottom">
                                        <h6 class="mb-0 fw-700">{{ translate('Messages') }}</h6>
                                    </div>
                                    <div class="c-scrollbar-light" style="overflow-y:auto;max-height:300px;">
                                        @forelse ($unseen_chat_threads as $key => $chat_thread_id)
                                            @php
                                            $chat = \App\Models\Chat::where('chat_thread_id', $chat_thread_id)->latest()->first();
                                            $current_user = Auth::user()->id;
                                            @endphp
                                            @if ($chat != null)
                                                <a href="{{ route('all.messages') }}" class="chat-user-item p-3 d-block text-inherit hov-bg-soft-primary border-bottom">
                                                    {{-- المحتوى يبقى كما هو --}}
                                                </a>
                                            @endif
                                        @empty
                                            <div class="text-center py-4">
                                                <i class="las la-frown la-4x mb-2 opacity-40"></i>
                                                <h4 class="h6">{{ translate('No New Messages') }}</h4>
                                            </div>
                                        @endforelse
                                    </div>
                                    <div class="border-top">
                                        <a href="{{ route('all.messages') }}" class="btn text-reset btn-block fw-600 bg-light">{{ translate('View All Messages') }}</a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-inline-item ml-2">
                                <a href="{{ route('logout') }}" class="btn btn-sm bg-primary-grad text-white fw-600 py-1 px-3 round-btn shadow-sm">{{translate('Logout')}}</a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <header class="aiz-header shadow-sm glass-header border-0">
        <div class="aiz-navbar position-relative py-2 py-lg-0">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="logo">
                        <a href="{{ route('home') }}" class="d-inline-block py-lg-15px">
                            <img src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="{{ env('APP_NAME') }}" class="mw-100 h-30px h-md-40px" onerror="this.src='{{ static_asset('assets/img/logo.png') }}'">
                        </a>
                    </div>
                    
                    <div class="d-flex align-items-center d-lg-none" style="gap: 12px;">
                        @if(Auth::check())
                            <a href="{{ route('all.messages') }}" class="position-relative text-dark d-flex align-items-center">
                                <i class="las la-envelope fs-26"></i>
                                @if(isset($unseen_chat_thread_count) && $unseen_chat_thread_count > 0)
                                    <span class="badge badge-dot badge-sm badge-status no-border badge-danger pulse-badge"></span>
                                @endif
                            </a>
                            
                            {{-- 🛑 الزر الجديد بدون اللون الزهري ومقاسات فخمة 🛑 --}}
                            <button class="btn btn-mobile-explicit rounded-pill" onclick="toggleMobileMenu()">
                                <i class="las la-bars fs-18"></i>
                                <span>القائمة السريعة</span>
                                            </button>
                        @else
                            <button class="btn btn-mobile-explicit rounded-pill" onclick="toggleMobileMenu()">
                                <i class="las la-bars fs-18"></i>
                                <span>القائمة السريعة</span>
                            </button>
                        @endif
                    </div>

                    <ul class="mb-0 pl-0 ml-lg-auto d-none d-lg-flex align-items-stretch justify-content-center justify-content-lg-start">
                        <li class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['home'],'active') }}">
                            <a class="nav-link premium-nav-link text-uppercase fw-700 fs-15 d-flex align-items-center py-4" href="{{ route('home') }}">
                                <span class="text-dark mb-n1">{{ translate('Home') }}</span>
                            </a>
                        </li>
                        <li class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['member.listing'],'active') }}">
                            <a class="nav-link premium-nav-link text-uppercase fw-700 fs-15 d-flex align-items-center py-4"
                               href="{{ route('member.listing') }}">
                                <span class="text-dark mb-n1">{{ translate('Active Members') }}</span>
                            </a>
                        </li>
                        <li class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['packages'],'active') }}">
                            <a class="nav-link premium-nav-link text-uppercase fw-700 fs-15 d-flex align-items-center py-4" href="{{ route('packages') }}">
                                <span class="text-dark mb-n1">{{ translate('Premium Plans') }}</span>
                            </a>
                        </li>
                        <li class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['contact_us'],'active') }}">
                            <a class="nav-link premium-nav-link text-uppercase fw-700 fs-15 d-flex align-items-center py-4" href="{{ route('contact_us') }}">
                                <span class="text-dark mb-n1">{{ translate('Contact Us') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </header>
</div>

{{-- القائمة الجانبية للتنقل العام فقط؛ لا تعرض روابط الحساب الشخصي أو التسجيل والدخول. --}}
<div class="mobile-menu-overlay" onclick="toggleMobileMenu()"></div>
<div class="mobile-side-menu" id="mobileMenu">
    <div class="mobile-menu-header text-center pb-4">
        <button class="close-mobile-menu" onclick="toggleMobileMenu()"><i class="las la-times fs-20"></i></button>
        <i class="las la-heart text-white opacity-75" style="font-size: 60px;"></i>
        <h6 class="mb-0 text-white mt-3 fw-800">مرحباً بك في وصال</h6>
    </div>
    <div class="p-4 pb-3">
        <div class="mobile-nav-heading">قائمة التنقل بالموقع</div>
        <a href="{{ route('home') }}" class="mobile-nav-item"><i class="las la-home mobile-nav-icon"></i> الرئيسية</a>
        <a href="{{ route('member.listing') }}" class="mobile-nav-item"><i class="las la-users mobile-nav-icon"></i> الأعضاء النشطين</a>
        <a href="{{ route('packages') }}" class="mobile-nav-item"><i class="las la-gem mobile-nav-icon"></i> الباقات المميزة</a>
        <a href="{{ route('contact_us') }}" class="mobile-nav-item border-0"><i class="las la-headset mobile-nav-icon"></i> تواصل معنا</a>
        @if (Auth::check())
            <a href="{{ route('logout') }}" class="mobile-nav-item text-danger mt-3 border-top"><i class="las la-sign-out-alt mobile-nav-icon text-danger"></i> تسجيل الخروج</a>
        @endif
    </div>
</div>
<script>
    function toggleMobileMenu() {
        $('#mobileMenu').toggleClass('show');
        $('.mobile-menu-overlay').toggleClass('show');
    }
</script>