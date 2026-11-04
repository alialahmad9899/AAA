@extends('frontend.layouts.app')

@section('meta_title'){{ $page->meta_title ?: $page->title }}@stop

@section('meta_description'){{ $page->meta_description }}@stop

@section('meta_keywords'){{ $page->tags }}@stop

@section('meta')
    <!-- Schema.org markup -->
    <meta itemprop="name" content="{{ $page->meta_title ?: $page->title }}">
    <meta itemprop="description" content="{{ $page->meta_description }}">
    <meta itemprop="image" content="{{ uploaded_asset($page->meta_img) }}">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $page->meta_title ?: $page->title }}">
    <meta name="twitter:description" content="{{ $page->meta_description }}">
    <meta name="twitter:image" content="{{ uploaded_asset($page->meta_img) }}">

    <!-- Open Graph data -->
    <meta property="og:title" content="{{ $page->meta_title ?: $page->title }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:image" content="{{ uploaded_asset($page->meta_img) }}" />
    <meta property="og:description" content="{{ $page->meta_description }}" />
    <meta property="og:site_name" content="{{ get_setting('website_name') }}" />
@endsection

@section('content')
@if ($page->slug === 'terms-conditions')
    <main class="wesal-legal-page" aria-labelledby="wesal-legal-title">
        <div class="wesal-legal-container">
            <div class="wesal-legal-topline">
                <a href="{{ route('home') }}" class="wesal-legal-back">
                    <i class="las la-arrow-right" aria-hidden="true"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'العودة إلى الرئيسية' : 'Back to home' }}</span>
                </a>
                <span class="wesal-legal-document-type">
                    <i class="las la-shield-alt" aria-hidden="true"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'مركز الوثائق والسياسات' : 'Policies & legal information' }}</span>
                </span>
            </div>

            <header class="wesal-legal-hero">
                <div class="wesal-legal-hero-glow" aria-hidden="true"></div>
                <div class="wesal-legal-brand">
                    <span class="wesal-legal-brand-mark"><i class="las la-heart" aria-hidden="true"></i></span>
                    <span>WESAL <small>{{ app()->getLocale() === 'ar' ? 'وصال' : 'Wesal' }}</small></span>
                </div>
                <div class="wesal-legal-hero-content">
                    <span class="wesal-legal-eyebrow">{{ app()->getLocale() === 'ar' ? 'وضوح • ثقة • خصوصية' : 'CLARITY • TRUST • PRIVACY' }}</span>
                    <h1 id="wesal-legal-title">{{ $page->title }}</h1>
                    <p>{{ app()->getLocale() === 'ar' ? 'يرجى الاطلاع على البنود التالية بعناية لمعرفة الإطار الذي ينظّم استخدام خدمات وصال.' : 'Please review the following information to understand the terms that govern your use of Wesal services.' }}</p>
                </div>
                <div class="wesal-legal-hero-bottom">
                    <span><i class="las la-file-alt" aria-hidden="true"></i> {{ app()->getLocale() === 'ar' ? 'وثيقة رسمية' : 'Official document' }}</span>
                    @if ($page->updated_at)
                        <time datetime="{{ $page->updated_at->toDateString() }}">
                            <i class="las la-calendar-alt" aria-hidden="true"></i>
                            {{ app()->getLocale() === 'ar' ? 'آخر تحديث:' : 'Last updated:' }}
                            {{ $page->updated_at->format('d/m/Y') }}
                        </time>
                    @endif
                </div>
            </header>

            <section class="wesal-legal-paper" aria-label="{{ $page->title }}">
                <div class="wesal-legal-paper-accent" aria-hidden="true"></div>
                <div class="wesal-legal-content">
                    {!! $page->content !!}
                </div>
            </section>

            <div class="wesal-legal-endnote">
                <span class="wesal-legal-endnote-icon"><i class="las la-lock" aria-hidden="true"></i></span>
                <p>{{ app()->getLocale() === 'ar' ? 'نحرص في وصال على تقديم تجربة واضحة وآمنة، ونقدّر وقتك وثقتك.' : 'At Wesal, we value a clear and secure experience, and appreciate your time and trust.' }}</p>
                <a href="{{ route('home') }}">{{ app()->getLocale() === 'ar' ? 'العودة إلى وصال' : 'Return to Wesal' }} <i class="las la-arrow-left" aria-hidden="true"></i></a>
            </div>
        </div>
    </main>

    <style>
        .wesal-legal-page {
            --legal-navy: #101b33;
            --legal-navy-soft: #1c2b49;
            --legal-gold: #d5b45c;
            --legal-gold-deep: #b38d35;
            --legal-ink: #283449;
            --legal-muted: #718096;
            --legal-line: #e9edf3;
            min-height: 70vh;
            padding: clamp(22px, 4vw, 46px) 16px 58px;
            background:
                radial-gradient(ellipse at 8% 5%, rgba(213, 180, 92, .10), transparent 30%),
                radial-gradient(ellipse at 95% 38%, rgba(16, 27, 51, .045), transparent 32%),
                #f5f7fb;
            color: var(--legal-ink);
            font-family: 'Cairo', sans-serif;
        }
        .wesal-legal-container { width: 100%; max-width: 1030px; margin: 0 auto; }
        .wesal-legal-topline {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; margin: 0 2px 20px;
        }
        .wesal-legal-back, .wesal-legal-document-type {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 700; text-decoration: none;
        }
        .wesal-legal-back { color: #59667a; transition: color .2s ease, transform .2s ease; }
        .wesal-legal-back:hover { color: var(--legal-gold-deep); transform: translateX(2px); }
        .wesal-legal-back i { font-size: 17px; }
        .wesal-legal-document-type {
            color: #80662f; background: rgba(213, 180, 92, .13);
            border: 1px solid rgba(179, 141, 53, .20); border-radius: 999px;
            padding: 7px 13px;
        }
        .wesal-legal-document-type i { font-size: 16px; }
        .wesal-legal-hero {
            position: relative; isolation: isolate; overflow: hidden;
            padding: clamp(27px, 5vw, 54px) clamp(22px, 5vw, 55px) 22px;
            min-height: 280px; border-radius: 26px;
            color: #fff; background: linear-gradient(125deg, #101b33 0%, #172642 58%, #233653 100%);
            box-shadow: 0 18px 45px rgba(16, 27, 51, .16);
        }
        .wesal-legal-hero::before {
            content: ''; position: absolute; z-index: -1; inset: 0;
            opacity: .18; pointer-events: none;
            background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 38px 38px;
            mask-image: linear-gradient(110deg, #000, transparent 72%);
        }
        .wesal-legal-hero-glow {
            position: absolute; z-index: -1; width: 330px; height: 330px;
            inset-inline-end: -90px; top: -155px; border-radius: 50%;
            border: 1px solid rgba(213, 180, 92, .22);
            box-shadow: 0 0 0 28px rgba(213, 180, 92, .035), 0 0 0 58px rgba(213, 180, 92, .025);
        }
        .wesal-legal-brand {
            display: inline-flex; align-items: center; gap: 10px;
            color: #f4e5bd; font-size: 15px; font-weight: 800; letter-spacing: 1.7px;
        }
        .wesal-legal-brand small {
            display: block; margin-top: 1px; color: rgba(255,255,255,.58);
            font-size: 10px; letter-spacing: 0; font-weight: 600;
        }
        .wesal-legal-brand-mark {
            display: grid; place-items: center; width: 38px; height: 38px;
            color: var(--legal-gold); border: 1px solid rgba(213, 180, 92, .48);
            border-radius: 12px; background: rgba(213, 180, 92, .10); font-size: 20px;
        }
        .wesal-legal-hero-content { max-width: 750px; margin-top: 25px; }
        .wesal-legal-eyebrow {
            display: inline-block; margin-bottom: 10px; color: var(--legal-gold);
            font-size: 11px; font-weight: 800; letter-spacing: 1.15px;
        }
        .wesal-legal-hero h1 {
            margin: 0 0 10px; color: #fff; font-size: clamp(27px, 4vw, 41px);
            font-weight: 800; line-height: 1.45; letter-spacing: -.5px;
        }
        .wesal-legal-hero-content p {
            max-width: 660px; margin: 0; color: rgba(241,245,249,.78);
            font-size: 14px; line-height: 2; font-weight: 500;
        }
        .wesal-legal-hero-bottom {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 10px; margin-top: 24px; padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,.12);
            color: rgba(226,232,240,.72); font-size: 11px; font-weight: 600;
        }
        .wesal-legal-hero-bottom span, .wesal-legal-hero-bottom time { display: inline-flex; align-items: center; gap: 7px; }
        .wesal-legal-hero-bottom i { color: var(--legal-gold); font-size: 15px; }
        .wesal-legal-paper {
            position: relative; margin-top: 22px; padding: clamp(23px, 4.8vw, 52px);
            border: 1px solid rgba(226,232,240,.95); border-radius: 22px;
            background: #fff; box-shadow: 0 12px 36px rgba(16,27,51,.055);
        }
        .wesal-legal-paper-accent {
            position: absolute; top: 0; inset-inline-start: 42px;
            width: 74px; height: 4px; border-radius: 0 0 8px 8px;
            background: linear-gradient(90deg, #b38d35, #e3ce8d);
        }
        .wesal-legal-content {
            color: #364357; font-size: 15px; line-height: 2.15;
            font-weight: 500; overflow-wrap: anywhere; word-break: normal;
            text-align: start;
        }
        .wesal-legal-content > *:first-child { margin-top: 0 !important; }
        .wesal-legal-content > *:last-child { margin-bottom: 0 !important; }
        .wesal-legal-content h1, .wesal-legal-content h2, .wesal-legal-content h3,
        .wesal-legal-content h4, .wesal-legal-content h5, .wesal-legal-content h6 {
            color: var(--legal-navy); font-family: 'Cairo', sans-serif;
            font-weight: 800; line-height: 1.65; text-align: start;
        }
        .wesal-legal-content h1 { font-size: 25px; margin: 30px 0 14px; }
        .wesal-legal-content h2 {
            position: relative; margin: 34px 0 14px; padding-inline-start: 15px;
            border-inline-start: 3px solid var(--legal-gold); font-size: 21px;
        }
        .wesal-legal-content h3 { margin: 25px 0 10px; color: #34435d; font-size: 17px; }
        .wesal-legal-content h4, .wesal-legal-content h5, .wesal-legal-content h6 { margin: 20px 0 9px; font-size: 15px; }
        .wesal-legal-content p { margin: 0 0 17px; color: #4b586c; }
        .wesal-legal-content strong, .wesal-legal-content b { color: #202e46; font-weight: 800; }
        .wesal-legal-content ul, .wesal-legal-content ol { margin: 10px 0 22px; padding-inline-start: 28px; }
        .wesal-legal-content li { margin: 0 0 9px; padding-inline-start: 4px; color: #4b586c; }
        .wesal-legal-content li::marker { color: var(--legal-gold-deep); font-weight: 800; }
        .wesal-legal-content a { color: #9a7629; font-weight: 700; text-decoration: underline; text-decoration-color: rgba(154,118,41,.38); text-underline-offset: 3px; }
        .wesal-legal-content a:hover { color: #6f531c; text-decoration-color: currentColor; }
        .wesal-legal-content blockquote {
            margin: 22px 0; padding: 16px 20px; border: 0;
            border-inline-start: 3px solid var(--legal-gold);
            border-radius: 10px; background: #faf8f1; color: #5d543d;
        }
        .wesal-legal-content hr { margin: 28px 0; border: 0; border-top: 1px solid var(--legal-line); }
        .wesal-legal-content img, .wesal-legal-content video, .wesal-legal-content iframe { max-width: 100%; border-radius: 12px; }
        .wesal-legal-content table { width: 100%; max-width: 100%; margin: 18px 0; border-collapse: collapse; display: block; overflow-x: auto; }
        .wesal-legal-content th, .wesal-legal-content td { min-width: 110px; padding: 11px 13px; border: 1px solid var(--legal-line); text-align: start; vertical-align: top; }
        .wesal-legal-content th { background: #f7f8fb; color: var(--legal-navy); font-weight: 800; }
        .wesal-legal-content code { padding: 2px 6px; border-radius: 5px; background: #f1f4f8; color: #34435d; }
        .wesal-legal-endnote {
            display: flex; align-items: center; gap: 13px; margin-top: 20px;
            padding: 17px 20px; border: 1px solid rgba(213,180,92,.25);
            border-radius: 16px; background: rgba(255,255,255,.72);
        }
        .wesal-legal-endnote-icon {
            display: grid; place-items: center; flex: 0 0 38px; width: 38px; height: 38px;
            border-radius: 12px; background: rgba(213,180,92,.15); color: #9a7629; font-size: 19px;
        }
        .wesal-legal-endnote p { flex: 1; margin: 0; color: #647084; font-size: 12px; line-height: 1.9; }
        .wesal-legal-endnote a { color: #816326; white-space: nowrap; font-size: 12px; font-weight: 800; text-decoration: none; }
        .wesal-legal-endnote a:hover { color: #4b3b1b; }
        @media (max-width: 575.98px) {
            .wesal-legal-page { padding: 18px 12px 34px; }
            .wesal-legal-topline { align-items: flex-start; }
            .wesal-legal-document-type { max-width: 52%; padding: 6px 10px; font-size: 10px; }
            .wesal-legal-hero { min-height: 0; padding: 25px 21px 17px; border-radius: 20px; }
            .wesal-legal-hero-content { margin-top: 21px; }
            .wesal-legal-hero h1 { font-size: 27px; }
            .wesal-legal-hero-content p { font-size: 12px; }
            .wesal-legal-hero-bottom { margin-top: 19px; }
            .wesal-legal-paper { margin-top: 14px; padding: 27px 19px 22px; border-radius: 18px; }
            .wesal-legal-paper-accent { inset-inline-start: 24px; }
            .wesal-legal-content { font-size: 13px; line-height: 2.05; }
            .wesal-legal-content h1 { font-size: 21px; }
            .wesal-legal-content h2 { margin-top: 27px; font-size: 18px; }
            .wesal-legal-content h3 { font-size: 15px; }
            .wesal-legal-endnote { align-items: flex-start; flex-wrap: wrap; padding: 14px; }
            .wesal-legal-endnote p { flex-basis: calc(100% - 53px); }
            .wesal-legal-endnote a { margin-inline-start: 51px; }
        }
    </style>
@else
    <section class="pt-4 mb-4">
        <div class="container text-center">
            <div class="row">
                <div class="col-lg-6 text-center text-lg-left">
                    <h1 class="fw-600 h4">{{ $page->title }}</h1>
                </div>
                <div class="col-lg-6">
                    <ul class="breadcrumb bg-transparent p-0 justify-content-center justify-content-lg-end">
                        <li class="breadcrumb-item opacity-50">
                            <a class="text-reset" href="{{ route('home') }}">{{ translate('Home')}}</a>
                        </li>
                        <li class="text-dark fw-600 breadcrumb-item">
                            <a class="text-reset" href="{{ route('custom-pages.show_custom_page', $page->slug ) }}">"{{ $page->title }}"</a>
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
