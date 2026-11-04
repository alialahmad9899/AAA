@extends('frontend.layouts.app')

@php
    $isWesalLegalPage = $page->slug === 'terms-conditions';
    $isArabicLegalPage = \Illuminate\Support\Str::startsWith(app()->getLocale(), 'ar');
    $wesalLegalTitle = $isArabicLegalPage
        ? 'سياسة الخصوصية والشروط والأحكام'
        : 'Privacy Policy & Terms of Use';
    $wesalLegalDescription = $isArabicLegalPage
        ? 'تعرّف على كيفية تعامل وصال مع معلوماتك، والضوابط التي تنظّم استخدامك للمنصة.'
        : 'Learn how Wesal handles your information and the rules that govern your use of the platform.';
@endphp

@section('meta_title'){{ $isWesalLegalPage ? $wesalLegalTitle . ' | وصال' : ($page->meta_title ?: $page->title) }}@stop

@section('meta_description'){{ $isWesalLegalPage ? $wesalLegalDescription : $page->meta_description }}@stop

@section('meta_keywords'){{ $page->tags }}@stop

@section('meta')
    <meta itemprop="name" content="{{ $isWesalLegalPage ? $wesalLegalTitle : ($page->meta_title ?: $page->title) }}">
    <meta itemprop="description" content="{{ $isWesalLegalPage ? $wesalLegalDescription : $page->meta_description }}">
    <meta itemprop="image" content="{{ uploaded_asset($page->meta_img) }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $isWesalLegalPage ? $wesalLegalTitle : ($page->meta_title ?: $page->title) }}">
    <meta name="twitter:description" content="{{ $isWesalLegalPage ? $wesalLegalDescription : $page->meta_description }}">
    <meta name="twitter:image" content="{{ uploaded_asset($page->meta_img) }}">
    <meta property="og:title" content="{{ $isWesalLegalPage ? $wesalLegalTitle : ($page->meta_title ?: $page->title) }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ uploaded_asset($page->meta_img) }}">
    <meta property="og:description" content="{{ $isWesalLegalPage ? $wesalLegalDescription : $page->meta_description }}">
    <meta property="og:site_name" content="{{ get_setting('website_name') }}">
@endsection

@section('content')
@if ($isWesalLegalPage)
    <main class="wesal-legal-page" aria-labelledby="wesal-legal-title">
        <div class="wesal-legal-shell">
            <div class="wesal-legal-brand" aria-label="وصال">
                <span class="wesal-legal-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 40 40" role="presentation" focusable="false">
                        <path d="M20 30.1 8.9 19.4a7.1 7.1 0 0 1 10.1-10l1 1 1-1a7.1 7.1 0 0 1 10.1 10L20 30.1Z" />
                        <path d="M8.7 31.8h22.6" />
                    </svg>
                </span>
                <span class="wesal-legal-brand-name">وصال</span>
                <span class="wesal-legal-brand-caption">WESAL</span>
            </div>

            <header class="wesal-legal-intro">
                <span class="wesal-legal-kicker">
                    <span class="wesal-legal-kicker-dot" aria-hidden="true"></span>
                    {{ $isArabicLegalPage ? 'الوثائق والسياسات' : 'POLICIES & LEGAL' }}
                </span>
                <h1 id="wesal-legal-title">{{ $wesalLegalTitle }}</h1>
                <p class="wesal-legal-lead">{{ $wesalLegalDescription }}</p>
                @if ($page->updated_at)
                    <div class="wesal-legal-updated">
                        <span class="wesal-legal-updated-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z" /></svg>
                        </span>
                        <span>{{ $isArabicLegalPage ? 'آخر تحديث' : 'Last updated' }}</span>
                        <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->format('d/m/Y') }}</time>
                    </div>
                @endif
                <span class="wesal-legal-intro-accent" aria-hidden="true"></span>
            </header>

            <nav class="wesal-legal-index" id="wesal-legal-index" aria-label="{{ $isArabicLegalPage ? 'أقسام الوثيقة' : 'Document sections' }}" hidden>
                <span class="wesal-legal-index-label">{{ $isArabicLegalPage ? 'في هذه الصفحة' : 'IN THIS DOCUMENT' }}</span>
                <div class="wesal-legal-index-links" id="wesal-legal-index-links"></div>
            </nav>

            <article class="wesal-legal-document" id="wesal-legal-document" aria-label="{{ $wesalLegalTitle }}">
                {!! $page->content !!}
            </article>

            <div class="wesal-legal-signoff">
                <span class="wesal-legal-signoff-line" aria-hidden="true"></span>
                <span>{{ $isArabicLegalPage ? 'وصال — مساحة للثقة والاحترام' : 'Wesal — A space for trust and respect' }}</span>
                <span class="wesal-legal-signoff-line" aria-hidden="true"></span>
            </div>
        </div>
    </main>

    <style>
        body.wesal-terms-document-body,
        body.wesal-terms-document-body .aiz-main-wrapper {
            background: #FDFBF7 !important;
        }
        body.wesal-terms-document-body { min-width: 320px; margin: 0 !important; padding: 0 !important; }
        body.wesal-terms-document-body .aiz-main-wrapper {
            display: block !important;
            width: 100% !important;
            min-height: 100svh !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }
        .wesal-legal-page {
            --wesal-teal: #0F4C5C;
            --wesal-teal-light: #1F6675;
            --wesal-coral: #E07A5F;
            --wesal-coral-soft: #F2B5A0;
            --wesal-ivory: #FDFBF7;
            --wesal-ink: #1D2D44;
            --wesal-muted: #6C7A89;
            --wesal-border: #EAE7E0;
            box-sizing: border-box;
            width: 100%;
            min-height: 100svh;
            margin: 0;
            padding: calc(18px + env(safe-area-inset-top)) 14px calc(28px + env(safe-area-inset-bottom));
            background:
                radial-gradient(ellipse at 92% 0%, rgba(242,181,160,.16), transparent 34%),
                radial-gradient(ellipse at 0% 26%, rgba(15,76,92,.07), transparent 30%),
                var(--wesal-ivory);
            color: var(--wesal-ink);
            font-family: 'Cairo', sans-serif;
            direction: inherit;
            -webkit-text-size-adjust: 100%;
        }
        .wesal-legal-page *, .wesal-legal-page *::before, .wesal-legal-page *::after { box-sizing: border-box; }
        .wesal-legal-shell { width: 100%; max-width: 790px; margin-inline: auto; }
        .wesal-legal-brand {
            display: flex; align-items: center; justify-content: center; gap: 9px;
            min-height: 46px; margin: 0 auto 17px; color: var(--wesal-teal);
        }
        .wesal-legal-brand-mark {
            display: grid; place-items: center; width: 38px; height: 38px;
            border-radius: 13px; color: #fff; background: var(--wesal-teal);
            box-shadow: 0 5px 13px rgba(15,76,92,.16);
        }
        .wesal-legal-brand-mark svg { width: 25px; height: 25px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .wesal-legal-brand-name { font-size: 18px; line-height: 1; font-weight: 800; }
        .wesal-legal-brand-caption { align-self: center; padding-inline-start: 9px; border-inline-start: 1px solid rgba(15,76,92,.18); color: var(--wesal-muted); font: 700 9px/1.2 Arial,sans-serif; letter-spacing: 1.5px; }
        .wesal-legal-intro {
            position: relative; overflow: hidden; padding: 25px 25px 22px;
            border: 1px solid rgba(15,76,92,.10); border-radius: 22px;
            background: rgba(255,255,255,.88);
            box-shadow: 0 9px 28px rgba(29,45,68,.055);
        }
        .wesal-legal-kicker {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 11px;
            color: var(--wesal-teal-light); font-size: 11px; font-weight: 800; letter-spacing: .2px;
        }
        .wesal-legal-kicker-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--wesal-coral); box-shadow: 0 0 0 4px rgba(224,122,95,.12); }
        .wesal-legal-intro h1 {
            max-width: 660px; margin: 0; color: var(--wesal-teal);
            font-size: clamp(25px, 5vw, 34px); font-weight: 800;
            line-height: 1.48; letter-spacing: -.35px;
        }
        .wesal-legal-lead { max-width: 620px; margin: 9px 0 0; color: var(--wesal-muted); font-size: 13px; font-weight: 500; line-height: 1.95; }
        .wesal-legal-updated { display: inline-flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 15px; color: var(--wesal-muted); font-size: 11px; font-weight: 600; }
        .wesal-legal-updated-icon { display: inline-flex; width: 16px; height: 16px; color: var(--wesal-coral); }
        .wesal-legal-updated-icon svg { width: 100%; height: 100%; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
        .wesal-legal-updated time { color: var(--wesal-teal); font-weight: 800; }
        .wesal-legal-intro-accent { position: absolute; inset-inline-end: 0; bottom: 0; width: 64px; height: 4px; border-radius: 4px 0 0 0; background: var(--wesal-coral); }
        .wesal-legal-index { margin: 14px 0 0; padding: 13px 15px; border: 1px solid rgba(31,102,117,.12); border-radius: 16px; background: rgba(31,102,117,.045); }
        .wesal-legal-index[hidden] { display: none !important; }
        .wesal-legal-index-label { display: block; margin-bottom: 9px; color: var(--wesal-muted); font-size: 9px; font-weight: 800; letter-spacing: .8px; }
        .wesal-legal-index-links { display: flex; flex-wrap: wrap; gap: 8px; }
        .wesal-legal-index a { display: inline-flex; align-items: center; min-height: 34px; padding: 6px 12px; border: 1px solid rgba(15,76,92,.12); border-radius: 999px; color: var(--wesal-teal); background: #fff; font-size: 11px; font-weight: 800; line-height: 1.5; text-decoration: none; transition: background .18s ease,border-color .18s ease; }
        .wesal-legal-index a:hover, .wesal-legal-index a:focus-visible { border-color: var(--wesal-coral); background: #fff8f5; color: var(--wesal-teal); outline: none; }
        .wesal-legal-document {
            width: 100%; min-width: 0; margin-top: 14px; padding: 25px 26px;
            border: 1px solid var(--wesal-border); border-radius: 20px;
            background: #fff; box-shadow: 0 7px 24px rgba(29,45,68,.045);
            color: var(--wesal-ink); font-size: 14px; font-weight: 500;
            line-height: 2.05; text-align: start; overflow-wrap: anywhere; word-break: normal;
        }
        .wesal-legal-document > *:first-child { margin-top: 0 !important; }
        .wesal-legal-document > *:last-child { margin-bottom: 0 !important; }
        .wesal-legal-document h1, .wesal-legal-document h2, .wesal-legal-document h3,
        .wesal-legal-document h4, .wesal-legal-document h5, .wesal-legal-document h6 {
            color: var(--wesal-teal); font-family: 'Cairo',sans-serif; font-weight: 800;
            line-height: 1.65; text-align: start; scroll-margin-top: 18px;
        }
        .wesal-legal-document h1 { margin: 22px 0 12px; font-size: 22px; }
        .wesal-legal-document h2 {
            margin: 27px 0 12px; padding: 8px 13px;
            border-inline-start: 3px solid var(--wesal-coral);
            border-radius: 0 10px 10px 0; background: #F7FAF9;
            color: var(--wesal-teal); font-size: 18px;
        }
        .wesal-legal-document h3 { margin: 21px 0 9px; color: var(--wesal-teal-light); font-size: 16px; }
        .wesal-legal-document h4, .wesal-legal-document h5, .wesal-legal-document h6 { margin: 17px 0 8px; font-size: 14px; }
        .wesal-legal-document p { margin: 0 0 14px; color: #465566; }
        .wesal-legal-document strong, .wesal-legal-document b { color: var(--wesal-ink); font-weight: 800; }
        .wesal-legal-document ul, .wesal-legal-document ol { margin: 9px 0 18px; padding-inline-start: 23px; }
        .wesal-legal-document li { margin: 0 0 7px; padding-inline-start: 3px; color: #465566; }
        .wesal-legal-document li::marker { color: var(--wesal-coral); font-weight: 800; }
        .wesal-legal-document a { color: var(--wesal-teal-light); font-weight: 800; text-decoration: underline; text-decoration-color: rgba(31,102,117,.35); text-underline-offset: 3px; overflow-wrap: anywhere; }
        .wesal-legal-document a:hover { color: var(--wesal-coral); }
        .wesal-legal-document blockquote { margin: 17px 0; padding: 13px 16px; border: 0; border-inline-start: 3px solid var(--wesal-coral); border-radius: 10px; background: #FFF7F3; color: #68554F; }
        .wesal-legal-document hr { margin: 22px 0; border: 0; border-top: 1px solid var(--wesal-border); }
        .wesal-legal-document img, .wesal-legal-document video, .wesal-legal-document iframe { display: block; max-width: 100%; height: auto; border-radius: 10px; }
        .wesal-legal-document table { display: block; width: 100%; max-width: 100%; margin: 15px 0; overflow-x: auto; border-collapse: collapse; -webkit-overflow-scrolling: touch; }
        .wesal-legal-document th, .wesal-legal-document td { min-width: 95px; padding: 9px 11px; border: 1px solid var(--wesal-border); text-align: start; vertical-align: top; }
        .wesal-legal-document th { background: #F3F8F7; color: var(--wesal-teal); font-weight: 800; }
        .wesal-legal-document code { padding: 2px 5px; border-radius: 5px; background: #F2F5F4; color: var(--wesal-teal); }
        .wesal-legal-signoff { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0 0; color: #8A969C; font-size: 10px; font-weight: 700; text-align: center; }
        .wesal-legal-signoff-line { width: 28px; height: 1px; background: rgba(224,122,95,.55); }
        @media (min-width: 768px) {
            .wesal-legal-page { padding: 28px 24px 42px; }
            .wesal-legal-brand { margin-bottom: 20px; }
            .wesal-legal-intro { padding: 30px 34px 27px; border-radius: 24px; }
            .wesal-legal-lead { font-size: 14px; }
            .wesal-legal-document { margin-top: 16px; padding: 34px 38px; font-size: 15px; line-height: 2.1; border-radius: 22px; }
            .wesal-legal-document h2 { font-size: 20px; }
        }
        @media (max-width: 420px) {
            .wesal-legal-page { padding-inline: 10px; }
            .wesal-legal-intro { padding: 21px 18px 19px; border-radius: 18px; }
            .wesal-legal-intro h1 { font-size: 24px; line-height: 1.5; }
            .wesal-legal-lead { font-size: 12px; line-height: 1.9; }
            .wesal-legal-document { padding: 20px 16px; border-radius: 17px; font-size: 13px; line-height: 2; }
            .wesal-legal-document h1 { font-size: 20px; }
            .wesal-legal-document h2 { margin-top: 23px; padding: 7px 10px; font-size: 16px; }
            .wesal-legal-document h3 { font-size: 14px; }
            .wesal-legal-index { padding: 11px 12px; }
            .wesal-legal-index a { min-height: 32px; padding: 5px 10px; font-size: 10px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .wesal-legal-page *, .wesal-legal-page *::before, .wesal-legal-page *::after { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>

    <script>
        (function () {
            var article = document.getElementById('wesal-legal-document');
            var index = document.getElementById('wesal-legal-index');
            var links = document.getElementById('wesal-legal-index-links');
            if (!article || !index || !links) return;

            var headings = Array.prototype.slice.call(article.querySelectorAll('h1,h2,h3,h4'));
            var sections = { privacy: null, terms: null };
            var normalize = function (value) {
                return (value || '').toLocaleLowerCase()
                    .replace(/[\u064B-\u065F\u0670]/g, '')
                    .replace(/[أإآ]/g, 'ا')
                    .replace(/ى/g, 'ي')
                    .replace(/[^\p{L}\p{N}]+/gu, ' ')
                    .trim();
            };

            headings.forEach(function (heading) {
                var text = normalize(heading.textContent);
                if (!sections.privacy && (text.indexOf('سياسة الخصوصية') !== -1 || text.indexOf('privacy policy') !== -1)) {
                    sections.privacy = heading;
                }
                if (!sections.terms && ((text.indexOf('الشروط') !== -1 && (text.indexOf('الاحكام') !== -1 || text.indexOf('احكام') !== -1)) || text.indexOf('terms and conditions') !== -1 || text === 'terms')) {
                    sections.terms = heading;
                }
            });

            var entries = [];
            if (sections.privacy) entries.push({ heading: sections.privacy, label: {{ $isArabicLegalPage ? '\'سياسة الخصوصية\'' : '\'Privacy Policy\'' }} });
            if (sections.terms && sections.terms !== sections.privacy) entries.push({ heading: sections.terms, label: {{ $isArabicLegalPage ? '\'الشروط والأحكام\'' : '\'Terms & Conditions\'' }} });

            if (entries.length) {
                entries.forEach(function (entry, i) {
                    if (!entry.heading.id) entry.heading.id = 'wesal-legal-section-' + (i + 1);
                    var link = document.createElement('a');
                    link.href = '#' + entry.heading.id;
                    link.textContent = entry.label;
                    links.appendChild(link);
                });
                index.hidden = false;
            }
        })();
    </script>
@else
    <section class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mx-auto text-center">
                    <h1 class="fw-600 h4">{{ $page->title }}</h1>
                </div>
                <div class="col-lg-6">
                    <ul class="breadcrumb bg-transparent p-0 justify-content-center justify-content-lg-end">
                        <li class="breadcrumb-item opacity-50">
                            <a class="text-reset" href="{{ route('home') }}">{{ translate('Home') }}</a>
                        </li>
                        <li class="text-dark fw-600 breadcrumb-item">
                            <a class="text-reset" href="{{ route('custom-pages.show_custom_page', $page->slug) }}">"{{ $page->title }}"</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section class="mb-4">
        <div class="container">
            <div class="p-4 bg-white rounded shadow-sm overflow-hidden mw-100 text-left">
                {!! $page->content !!}
            </div>
        </div>
    </section>
@endif
@endsection
