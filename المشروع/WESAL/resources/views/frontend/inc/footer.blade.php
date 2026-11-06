<style>
    /* 🛑 اللمسات الملكية للفوتر والشريط السفلي (بدون المساس بالهيكلية) 🛑 */
    .aiz-footer {
        background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%) !important; /* كحلي ملكي متدرج */
        color: #e2e8f0 !important;
        border-top: 3px solid #D4AF37 !important; /* خط علوي ذهبي */
        font-family: 'Cairo', sans-serif;
    }

    .aiz-footer a {
        transition: all 0.3s ease;
    }
    .aiz-footer a:hover {
        color: #D4AF37 !important; /* ذهبي عند التمرير */
        text-decoration: none;
        padding-right: 5px; /* حركة خفيفة لليسار عند التمرير (باعتبار الموقع RTL) */
    }

    .footer-widget-title {
        color: #D4AF37 !important; /* العناوين بالذهبي */
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        position: relative;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* شريط الموبايل السفلي (تصميم نظيف وأنيق) */
    .aiz-mobile-bottom-nav {
        background-color: #ffffff !important;
        border-top: 1px solid rgba(0,0,0,0.05) !important;
        box-shadow: 0 -4px 25px rgba(15, 23, 42, 0.08) !important; /* ظل علوي ناعم */
        height: 65px;
        display: flex;
        align-items: center;
        z-index: 1030; 
        padding-bottom: env(safe-area-inset-bottom); /* لحماية أجهزة الآيفون */
    }

    /* أيقونات الموبايل */
    .aiz-mobile-bottom-nav i {
        font-size: 24px !important; /* تكبير الأيقونات قليلاً لتكون أوضح */
        transition: all 0.3s ease;
        color: #94a3b8;
    }
    .aiz-mobile-bottom-nav span {
        color: #64748b;
        transition: all 0.3s ease;
    }
    
    /* التأثير عند تفعيل الزر (Active) */
    .aiz-mobile-bottom-nav .active-link i, 
    .aiz-mobile-bottom-nav .active-link span {
        color: #D4AF37 !important; /* ذهبي ملكي */
        opacity: 1 !important;
    }
    .aiz-mobile-bottom-nav .active-link i {
        transform: translateY(-2px); /* الأيقونة ترتفع قليلاً عند التفعيل */
    }

    /* شارات التنبيه الحمراء (أنيقة ومقروءة) */
    .aiz-mobile-bottom-nav .badge {
        background-color: #ef4444 !important; /* أحمر ناعم وواضح */
        color: #fff !important;
        font-size: 10px !important;
        font-weight: 800 !important;
        min-width: 18px;
        height: 18px;
        line-height: 14px;
        border: 2px solid #fff !important;
        border-radius: 50%;
        top: -3px !important;
        right: 0px !important;
        box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
    }

    .footer-copyright {
        border-top: 1px solid rgba(255,255,255,0.05);
        padding-top: 20px;
        margin-top: 30px;
        opacity: 0.7;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    /* أيقونات السوشيال ميديا بالفوتر */
    .social-links a {
        background: rgba(255,255,255,0.05);
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: 0.3s;
    }
    .social-links a:hover {
        background: #D4AF37;
        transform: translateY(-3px);
    }
    .social-links a i {
        color: #fff !important;
    }
    
    /* ضمان ظهور القائمة الجانبية */
    .aiz-mobile-side-nav {
        z-index: 1040 !important;
    }
</style>

<footer class="aiz-footer fs-13 mt-auto text-white pt-5 pb-4">
    <div class="container text-right" dir="rtl">
        
        {{-- الجزء العلوي: اللوجو والوصف --}}
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <div class="footer-logo mb-3">
                    <a href="{{ route('home') }}" class="d-inline-block">
                        <img src="{{ get_setting('footer_logo') ? uploaded_asset(get_setting('footer_logo')) : static_asset('assets/img/logo.png') }}" 
                             alt="{{ env('APP_NAME') }}" class="mw-100" style="height: 55px;">
                    </a>
                </div>
                <p class="opacity-70 fs-16 lh-1-8 px-md-5">
                    {!! get_setting('about_us_description') !!}
                </p>
            </div>
        </div>

        {{-- الجزء الأوسط: الروابط السريعة فقط --}}
        <div class="row justify-content-center">
            @if ( !empty(get_setting('widget_one_labels')) )
            <div class="col-md-3 mb-4 text-center">
                <h4 class="footer-widget-title">{{ get_setting('widget_one_title') }}</h4>
                <ul class="list-unstyled">
                    @foreach (json_decode( get_setting('widget_one_labels'), true) as $key => $value)
                        <li class="mb-3">
                            <a href="{{ json_decode( get_setting('widget_one_links'), true)[$key] }}" class="text-white opacity-60 transition-03">{{ $value }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if ( !empty(get_setting('widget_two_labels')) )
            <div class="col-md-3 mb-4 text-center">
                <h4 class="footer-widget-title">{{ get_setting('widget_two_title') }}</h4>
                <ul class="list-unstyled">
                    @foreach (json_decode( get_setting('widget_two_labels'), true) as $key => $value)
                        <li class="my-3">
                            <a href="{{ json_decode( get_setting('widget_two_links'), true)[$key] }}" class="text-white opacity-60 transition-03">{{ $value }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if ( !empty(get_setting('widget_three_labels')) )
            <div class="col-md-3 mb-4 text-center">
                <h4 class="footer-widget-title">{{ get_setting('widget_three_title') }}</h4>
                <ul class="list-unstyled">
                    @foreach (json_decode( get_setting('widget_three_labels'), true) as $key => $value)
                        <li class="mb-3">
                            <a href="{{ json_decode( get_setting('widget_three_links'), true)[$key] }}" class="text-white opacity-60 transition-03">{{ $value }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        {{-- الجزء السفلي: الحقوق والسوشيال ميديا --}}
        <div class="footer-copyright">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-right mb-3 mb-md-0">
                    {!! get_setting('footer_copyright_text') !!}
                </div>
                <div class="col-md-6 text-center text-md-left">
                    <div class="social-links">
                        @if (get_setting('facebook_link')) <a href="{{ get_setting('facebook_link') }}" class="mx-1"><i class="lab la-facebook-f fs-18"></i></a> @endif
                        @if (get_setting('twitter_link')) <a href="{{ get_setting('twitter_link') }}" class="mx-1"><i class="lab la-twitter fs-18"></i></a> @endif
                        @if (get_setting('instagram_link')) <a href="{{ get_setting('instagram_link') }}" class="mx-1"><i class="lab la-instagram fs-18"></i></a> @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

{{-- شريط الموبايل السفلي المنسق --}}
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom">
    <div class="container-fluid">
        <div class="row align-items-center text-center">
            <div class="col">
                <a href="{{ route('home') }}" class="text-reset d-block py-2 {{ areActiveRoutes(['home'],'active-link')}}">
                    <i class="las la-home {{ areActiveRoutes(['home'],'text-primary','opacity-60')}}"></i>
                    <span class="d-block fs-10 fw-700 {{ areActiveRoutes(['home'],'text-primary','opacity-60')}}">{{ translate('Home') }}</span>
                </a>
            </div>
            @if (Auth::check() && Auth::user()->user_type == 'member')
                <div class="col">
                    <a href="{{ route('frontend.notifications') }}" class="text-reset d-block py-2 {{ areActiveRoutes(['frontend.notifications'],'active-link')}}">
                        <span class="d-inline-block position-relative px-2">
                            <i class="las la-bell {{ areActiveRoutes(['frontend.notifications'],'text-primary','opacity-60')}}"></i>
                            @php $u_count = \App\Models\Notification::where('notifiable_id', Auth::user()->id)->where('read_at', null)->count(); @endphp
                            @if($u_count > 0)<span class="badge position-absolute">{{ $u_count }}</span>@endif
                        </span>
                        <span class="d-block fs-10 fw-700 {{ areActiveRoutes(['frontend.notifications'],'text-primary','opacity-60')}}">{{ translate('Notifications') }}</span>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('all.messages') }}" class="text-reset d-block py-2 {{ areActiveRoutes(['all.messages'],'active-link')}}">
                        <span class="d-inline-block position-relative px-2">
                            <i class="las la-comment-dots {{ areActiveRoutes(['all.messages'],'text-primary','opacity-60')}}"></i>
                            @php $m_count = count(chat_threads()); @endphp
                            @if($m_count > 0)<span class="badge position-absolute">{{ $m_count }}</span>@endif
                        </span>
                        <span class="d-block fs-10 fw-700 {{ areActiveRoutes(['all.messages'],'text-primary','opacity-60')}}">{{ translate('Messages') }}</span>
                    </a>
                </div>
                <div class="col">
                    <a href="javascript:void(0)" class="text-reset d-block py-2 mobile-side-nav-thumb" data-toggle="class-toggle" data-target=".aiz-mobile-side-nav">
                        <span class="d-block mx-auto mb-1">
                            <img src="{{ uploaded_asset(Auth::user()->photo) }}" class="rounded-circle shadow-sm" style="width:24px; height:24px; border: 1px solid rgba(0,0,0,0.1); object-fit: cover;" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                        </span>
                        <span class="d-block fs-10 opacity-60 fw-700">{{ translate('Account') }}</span>
                    </a>
                </div>
            @else
                <div class="col">
                    <a href="{{ route('member.listing') }}" class="text-reset d-block py-2 {{ areActiveRoutes(['member.listing'],'active-link')}}">
                        <i class="las la-users {{ areActiveRoutes(['member.listing'],'text-primary','opacity-60')}}"></i>
                        <span class="d-block fs-10 fw-700">{{ translate('Active Members') }}</span>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('packages') }}" class="text-reset d-block py-2 {{ areActiveRoutes(['packages'],'active-link')}}">
                        <i class="las la-gem {{ areActiveRoutes(['packages'],'text-primary','opacity-60')}}"></i>
                        <span class="d-block fs-10 fw-700">{{ translate('Premium Plans') }}</span>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('contact_us') }}" class="text-reset d-block py-2 {{ areActiveRoutes(['contact_us'],'active-link')}}">
                        <i class="las la-headset {{ areActiveRoutes(['contact_us'],'text-primary','opacity-60')}}"></i>
                        <span class="d-block fs-10 fw-700">{{ translate('Contact Us') }}</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- إضافة القائمة الجانبية (Sidebar) لتعمل مع زر الحساب --}}
@if (Auth::check() && Auth::user()->user_type == 'member')
    <div class="aiz-mobile-side-nav collapse-sidebar-wrap sidebar-xl d-xl-none z-1035">
        <div class="overlay dark c-pointer overlay-fixed" data-toggle="class-toggle" data-target=".aiz-mobile-side-nav" data-same=".mobile-side-nav-thumb"></div>
        <div class="collapse-sidebar bg-white shadow-lg">
            @include('frontend.member.sidebar')
        </div>
    </div>
@endif
