<!DOCTYPE html>
<html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    
    <title>المحادثات | {{ get_setting('website_name') }}</title>
    <link rel="icon" href="{{ uploaded_asset(get_setting('site_icon')) }}">

    {{-- الخطوط والملفات الأساسية --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ static_asset('assets/css/vendors.css') }}">
    <link rel="stylesheet" href="{{ static_asset('assets/css/aiz-core.css') }}">
    <link rel="stylesheet" href="{{ static_asset('assets/css/bootstrap-rtl.min.css') }}">

    <style>
        /* 🛑 تصفير إجباري لكل شيء لضمان الشاشة الكاملة 🛑 */
        :root {
            --primary: #D4AF37; 
            --hov-primary: #B5952F; 
            --secondary: #0F172A; 
            --bg-body: #F0F2F5; 
        }

        html, body {
            font-family: 'Cairo', sans-serif !important;
            height: 100dvh !important; /* يأخذ طول الشاشة الحقيقي للموبايل */
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important; /* يمنع السكرول الخارجي نهائياً */
            background-color: var(--bg-body) !important;
            overscroll-behavior-y: none;
        }

        #chat-app-wrapper {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            overflow: hidden;
        }
    </style>
    @yield('header_style')
</head>
<body>

    {{-- حاوية الشات الرئيسية --}}
    <div id="chat-app-wrapper">
        @yield('content')
    </div>

    {{-- النوافذ المنبثقة --}}
    @yield('modal')

    {{-- ملفات الجافاسكريبت الأساسية --}}
    <script src="{{ static_asset('assets/js/vendors.js') }}"></script>
    <script src="{{ static_asset('assets/js/aiz-core.js') }}"></script>
    <script>
        if (window.jQuery) {
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': csrfMeta.getAttribute('content')
                    }
                });
            }
        }
    </script>
    
    {{-- إشعارات النظام --}}
    <script type="text/javascript">
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach
    </script>

    @yield('script')
</body>
</html>