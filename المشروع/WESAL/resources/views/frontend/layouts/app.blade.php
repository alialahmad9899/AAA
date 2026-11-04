@php
if (Session::has('locale')) {
    $locale = Session::get('locale', Config::get('app.locale'));
} else {
    $locale = env('DEFAULT_LANGUAGE');
}
$lang = \App\Models\Language::where('code', $locale)->first();
@endphp

<!DOCTYPE html>
@if (optional($lang)->rtl == 1)
    <html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ Route::currentRouteName() == 'all.messages' ? 'h-100' : '' }}">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ Route::currentRouteName() == 'all.messages' ? 'h-100' : '' }}">
@endif

<head>

     <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    <meta name="file-base-url" content="{{ getFileBaseURL() }}">
    <title>@yield('meta_title', get_setting('website_name') . ' | ' . get_setting('site_motto'))</title>

    <meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, interactive-widget=resizes-content">
<meta name="description" content="@yield('meta_description', get_setting('meta_description'))" />
    <meta name="keywords" content="@yield('meta_keywords', get_setting('meta_keywords'))">

    @yield('meta')

    @if (!isset($page))
        <meta itemprop="name" content="@yield('meta_title', config('app.name', env('APP_NAME')))" >
        <meta itemprop="description" content="@yield('meta_description', get_setting('meta_description'))">
        <meta itemprop="image" content="{{ uploaded_asset(get_setting('meta_image')) }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:site" content="@publisher_handle">
        <meta name="twitter:title" content="@yield('meta_title', config('app.name', env('APP_NAME')))" >
        <meta name="twitter:description" content="@yield('meta_description', get_setting('meta_description'))">
        <meta name="twitter:creator" content="@author_handle">
        <meta name="twitter:image" content="{{ uploaded_asset(get_setting('meta_image')) }}">

        <meta property="og:title" content="@yield('meta_title', config('app.name', env('APP_NAME')))" />
        <meta property="og:type" content="Business Site" />
        <meta property="og:url" content="{{ env('APP_URL') }}" />
        <meta property="og:image" content="{{ uploaded_asset(get_setting('meta_image')) }}" />
        <meta property="og:description" content="@yield('meta_description', get_setting('meta_description'))" />
        <meta property="og:site_name" content="{{ get_setting('website_name') }}" />
        <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">
    @endif

    <link rel="icon" href="{{ uploaded_asset(get_setting('site_icon')) }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"></noscript>

    @php
        $coreCssPath = public_path('assets/css/aiz-core.css');
        $coreCssVersion = is_file($coreCssPath) ? filemtime($coreCssPath) : '3.0';
    @endphp
    <link rel="stylesheet" href="{{ static_asset('assets/css/vendors.css') }}">
    <link rel="stylesheet" href="{{ static_asset('assets/css/aiz-core.css') }}?v={{ $coreCssVersion }}">

    @if (optional($lang)->rtl == 1)
        <link rel="stylesheet" href="{{ static_asset('assets/css/bootstrap-rtl.min.css') }}">
    @endif

    <script>
        var AIZ = AIZ || {};
    </script>
    
    <style>
        /* 🛑 ثورة الألوان والتصميم (Midnight Blue & Royal Gold) 🛑 */
        :root {
            --primary: #D4AF37; 
            --hov-primary: #B5952F; 
            --soft-primary: rgba(212, 175, 55, 0.15); 
            --secondary: #0F172A; 
            --soft-secondary: rgba(15, 23, 42, 0.15);
            --bg-body: #F8FAFC; 
        }

        body {
            font-family: 'Cairo', sans-serif !important;
            font-weight: 500;
            color: #334155;
            background-color: var(--bg-body) !important;
        }

        .aiz-main-wrapper {
            background-color: var(--bg-body) !important;
        }

        /* 🛑 شريط تمرير احترافي (Custom Scrollbar) 🛑 */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9; 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary); 
        }

        /* 🛑 الكلاسات الملكية العامة 🛑 */
        .text-primary-grad {
            background: linear-gradient(45deg, var(--secondary) 0%, var(--primary) 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }

        .bg-secondary-grad {
            background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%) !important;
        }

        .btn-primary,
        .bg-primary-grad,
        .badge-primary {
            background: linear-gradient(45deg, #d4af37, #b5952f) !important; 
            color: #fff !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3) !important;
            transition: all 0.3s ease !important;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5) !important;
            color: #fff !important;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary) !important;
            color: var(--primary) !important;
            background: transparent !important;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: var(--primary) !important;
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3) !important;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.05);
        }

        .shadow-premium {
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08) !important;
        }

        /* 🛑 تحسين النوافذ المنبثقة (Modals) 🛑 */
        .modal-content {
            border-radius: 20px !important;
            border: none !important;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2) !important;
            overflow: hidden;
        }
        .modal-backdrop.show {
            opacity: 0.6;
            backdrop-filter: blur(4px); 
        }

        .fill-dark {
            fill: var(--secondary) !important;
        }
        .fill-primary-grad stop:nth-child(1) {
            stop-color: var(--primary) !important;
        }
        .fill-primary-grad stop:nth-child(2) {
            stop-color: var(--hov-primary) !important;
        }
    </style>

    {{-- 🛑 الحل الجذري النهائي والقطعي لصفحة المحادثة 🛑 --}}
    @if(Route::currentRouteName() == 'all.messages')
    <style>
        /* شل حركة الموقع الأصلي تماماً وجعله بحجم الشاشة الفعلي */
        html, body {
            overflow: hidden !important; 
            height: 100dvh !important; /* dVH ضرورية جداً لموبايلات أبل وأندرويد الحديثة */
            width: 100vw !important;
            position: fixed !important; 
            top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            touch-action: none !important; /* يمنع الارتداد (Bounce) في الآيفون */
        }
        
        /* تدمير الهوامش اللي عم يفرضها القالب (الـ 172 بكسل المجهولة) */
        .aiz-main-wrapper {
            height: 100dvh !important;
            min-height: 100dvh !important;
            padding: 0 !important; /* إعدام أي Padding علوي أو سفلي */
            margin: 0 !important;
            display: block !important; /* نلغي الـ Flex تبع القالب لأنه عم يعارض الشات */
        }
        
        /* إخفاء شريط التنقل السفلي اللي بيطلع عالموبايل (أحد أسباب الفراغ) */
        .aiz-mobile-bottom-nav {
            display: none !important;
        }
    </style>
    @endif

    @if (get_setting('google_analytics_activation') == 1)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}"></script>

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', '{{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}');
        </script>
    @endif

    @if (get_setting('facebook_pixel_activation') == 1)
        <script>
            ! function(f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function() {
                    n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', {{ env('FACEBOOK_PIXEL_ID') }});
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ env('FACEBOOK_PIXEL_ID') }}/&ev=PageView&noscript=1" />
        </noscript>
        @endif

    {!! get_setting('header_script') !!}

</head>

@php
    $isTermsConditionsPage = Route::currentRouteName() === 'custom-pages.show_custom_page'
        && request()->route('slug') === 'terms-conditions';
    $isWesalLandingPage = in_array(Route::currentRouteName(), ['home', 'index'], true);
@endphp

<body class="text-left {{ Route::currentRouteName() == 'all.messages' ? 'h-100' : '' }} {{ $isTermsConditionsPage ? 'wesal-terms-document-body' : '' }} {{ $isWesalLandingPage ? 'wesal-landing-body' : '' }}">

    {{-- 🛑 تجريد الغلاف من الـ Flexbox والهوامش إذا كنا في صفحة المحادثة 🛑 --}}
    <div
        class="aiz-main-wrapper position-relative 
        @if (!$isWesalLandingPage && Route::currentRouteName() != 'all.messages' && !$isTermsConditionsPage)
            d-flex flex-column pt-8 pt-lg-10
        @endif
        {{ Route::currentRouteName() == 'all.messages' ? 'chat-wrapper-clean' : '' }}">

        @if (!$isTermsConditionsPage && !$isWesalLandingPage)
            @include('frontend.inc.header')
        @endif

        @yield('content')

        {{-- The terms-conditions route is a standalone legal document: no global header or footer. --}}
        @if (Route::currentRouteName() != 'all.messages' && !$isTermsConditionsPage && !$isWesalLandingPage)
            @include('frontend.inc.footer')
        @endif

    </div>

    @if (get_setting('show_cookies_agreement') == 'on')
        <div class="aiz-cookie-alert shadow-premium">
            <div class="p-3 rounded bg-secondary-grad text-white" style="border-radius: 15px !important;">
                <div class="mb-3 fs-14">
                    {{strip_tags(get_setting('cookies_agreement_text')) }}
                </div>
                <button class="btn btn-primary aiz-cookie-accepet w-100 fw-700" style="border-radius: 10px;">
                    {{ translate('Ok. I Understood') }}
                </button>
            </div>
        </div>
    @endif

    @yield('modal')

    <div class="modal fade account_status_change_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content p-4">
                <div class="modal-body text-center">
                    <form class="form-horizontal member-block" action="{{ route('member.account_deactivation') }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="deacticvation_status" id="deacticvation_status" value="">
                        <div class="mb-4">
                            <i class="las la-exclamation-circle text-warning" style="font-size: 70px;"></i>
                        </div>
                        <h4 class="modal-title h5 mb-3 font-weight-bold text-dark" id="confirmation_note" value=""></h4>
                        <hr class="my-4" style="border-color: rgba(0,0,0,0.05);">
                        <div class="d-flex justify-content-center" style="gap: 10px;">
                            <button type="submit" class="btn btn-primary px-5 fw-700" style="border-radius: 10px;">{{ translate('Yes') }}</button>
                            <button type="button" class="btn btn-light px-5 fw-700" data-dismiss="modal" style="border-radius: 10px;">{{ translate('No') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade account_delete_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content p-4">
                <div class="modal-body text-center">
                    <form class="form-horizontal member-block" action="{{ route('member.account_delete') }}"
                        method="POST">
                        @csrf                    
                        <div class="mb-4">
                            <i class="las la-trash-alt text-danger" style="font-size: 70px;"></i>
                        </div>
                        <h4 class="modal-title h5 mb-3 font-weight-bold text-dark" id="delete_confirmation_note" value=""></h4>
                        <hr class="my-4" style="border-color: rgba(0,0,0,0.05);">
                        <div class="d-flex justify-content-center" style="gap: 10px;">
                            <button type="submit" class="btn btn-danger px-5 fw-700" style="border-radius: 10px; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);">{{ translate('Yes') }}</button>
                            <button type="button" class="btn btn-light px-5 fw-700" data-dismiss="modal" style="border-radius: 10px;">{{ translate('No') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (get_setting('facebook_chat_activation') == 1)
        <script type="text/javascript">
            window.fbAsyncInit = function() {
                FB.init({
                    xfbml: true,
                    version: 'v3.3'
                });
            };

            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s);
                js.id = id;
                js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
        <div id="fb-root"></div>
        <div class="fb-customerchat" attribution=setup_tool page_id="{{ env('FACEBOOK_PAGE_ID') }}">
        </div>
    @endif

    <script src="{{ static_asset('assets/js/vendors.js') }}"></script>
    
    {{-- 🛑 كسر الكاش الجذري: سيتم تحميل الملف الجديد عند جميع الأعضاء فوراً 🛑 --}}
    @php
        $coreJsPath = public_path('assets/js/aiz-core.js');
        $coreJsVersion = is_file($coreJsPath) ? filemtime($coreJsPath) : '3.0';
    @endphp
    <script src="{{ static_asset('assets/js/aiz-core.js') }}?v={{ $coreJsVersion }}"></script>

    @if (get_setting('firebase_push_notification') == 1)
        @auth
            <script>
                (function () {
                    var firebaseStarted = false;

                    function loadFirebaseScript(src) {
                        return new Promise(function (resolve, reject) {
                            var script = document.createElement('script');
                            script.src = src;
                            script.async = true;
                            script.onload = resolve;
                            script.onerror = reject;
                            document.head.appendChild(script);
                        });
                    }

                    function initializeWesalMessaging() {
                        if (firebaseStarted || !('Notification' in window)) return;
                        firebaseStarted = true;

                        loadFirebaseScript('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js')
                            .then(function () {
                                return loadFirebaseScript('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');
                            })
                            .then(function () {
                                var firebaseConfig = {
                                    apiKey: "{{ env('FCM_API_KEY') }}",
                                    authDomain: "{{ env('FCM_AUTH_DOMAIN') }}",
                                    projectId: "{{ env('FCM_PROJECT_ID') }}",
                                    storageBucket: "{{ env('FCM_STORAGE_BUCKET') }}",
                                    messagingSenderId: "{{ env('FCM_MESSAGING_SENDER_ID') }}",
                                    appId: "{{ env('FCM_APP_ID') }}"
                                };

                                if (!firebase.apps.length) firebase.initializeApp(firebaseConfig);
                                var messaging = firebase.messaging();

                                messaging.requestPermission()
                                    .then(function () { return messaging.getToken(); })
                                    .then(function (token) {
                                        if (!token || !window.jQuery) return;
                                        $.ajax({
                                            url: '{{ route('fcmToken') }}',
                                            type: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                            },
                                            data: { fcm_token: token },
                                            dataType: 'JSON'
                                        });
                                    })
                                    .catch(function (error) {
                                        console.warn('Wesal push registration was not completed.', error);
                                    });

                                messaging.onMessage(function (payload) {
                                    var data = payload && payload.notification ? payload.notification : (payload.data || {});
                                    if ('Notification' in window && Notification.permission === 'granted') {
                                        new Notification(data.title || 'وصال', { body: data.body || '' });
                                    }
                                });
                            })
                            .catch(function (error) {
                                firebaseStarted = false;
                                console.warn('Wesal push scripts could not be loaded.', error);
                            });
                    }

                    if (document.readyState === 'complete') {
                        window.setTimeout(initializeWesalMessaging, 0);
                    } else {
                        window.addEventListener('load', initializeWesalMessaging, { once: true });
                    }
                })();
            </script>
        @endauth
    @endif

    @yield('script')

    <script type="text/javascript">
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach

        @if (Auth::check() && Auth::user()->user_type == 'member')
            function account_deactivation() {
                var status = {{ Auth::user()->deactivated }}
                $('.account_status_change_modal').modal('show');
                if (status == 0) {
                    $('#deacticvation_status').val(1);
                    $('#confirmation_note').html('{{ translate('Deactivating your account will prevent you from performing any actions. Are you sure you want to deactivate your account?') }}');
                } else {
                    $('#deacticvation_status').val(0);
                    $('#confirmation_note').html('{{ translate('Are You Sure To Reactive Your Account') }}');
                }
            }
        @endif
        @if (Auth::check() && Auth::user()->user_type == 'member')
            function account_delete() {
                var status = {{ Auth::user()->deactivated }}
                $('.account_delete_modal').modal('show');
                    $('#delete_confirmation_note').html('{{ translate('Do You Really Want To Delete Your Account') }}');
            }
        @endif
    </script>


    @if (env('DEMO_MODE') == 'On')
        <script type="text/javascript">
            // Login credentials autoFill for demo
            function autoFill1() {
                $('#email').val('user2@example.com');
                $('#password').val('12345678');
            }

            function autoFill2() {
                $('#email').val('user17@example.com');
                $('#password').val('12345678');
            }
        </script>
    @endif

    {!! get_setting('footer_script') !!}

</body>

</html>