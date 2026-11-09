@extends('admin.layouts.app')

@section('content')
@php
    $activeCount = count(array_filter($services, function ($service) {
        return !empty($service['active']) && empty($service['archived']);
    }));
    $archivedCount = count(array_filter($services, function ($service) {
        return !empty($service['archived']);
    }));
    $sortValues = array_map(function ($service) {
        return (int) ($service['sort_order'] ?? 0);
    }, $services);
    $nextSortOrder = count($sortValues) ? max($sortValues) + 10 : 10;

    $iconClasses = [
        'crown' => 'la-crown', 'campaign' => 'la-bullhorn', 'video' => 'la-video',
        'search' => 'la-search', 'user_search' => 'la-user-search',
        'handshake' => 'la-handshake', 'message' => 'la-comment-dots',
        'shield' => 'la-shield-alt', 'gift' => 'la-gift', 'link' => 'la-link',
        'support' => 'la-headset', 'star' => 'la-star',
    ];
    $inferFeatureIcon = function ($text) {
        $text = mb_strtolower((string) $text);
        if (str_contains($text, 'فيديو') || str_contains($text, 'video')) return 'video';
        if (str_contains($text, 'فيسبوك') || str_contains($text, 'تيك توك') || str_contains($text, 'منشور') || str_contains($text, 'social') || str_contains($text, 'post')) return 'campaign';
        if (str_contains($text, 'واتساب') || str_contains($text, 'تواصل') || str_contains($text, 'رسالة') || str_contains($text, 'contact') || str_contains($text, 'whatsapp')) return 'message';
        if (str_contains($text, 'رابط') || str_contains($text, 'بروفايل') || str_contains($text, 'link') || str_contains($text, 'profile')) return 'link';
        if (str_contains($text, 'خصوصية') || str_contains($text, 'حماية') || str_contains($text, 'تأمين') || str_contains($text, 'privacy') || str_contains($text, 'security')) return 'shield';
        if (str_contains($text, 'بحث') || str_contains($text, 'search') || str_contains($text, 'database')) return 'user_search';
        if (str_contains($text, 'توفيق') || str_contains($text, 'ربط') || str_contains($text, 'تنسيق') || str_contains($text, 'match')) return 'handshake';
        if (str_contains($text, 'ميزة') || str_contains($text, 'هدية') || str_contains($text, 'gift')) return 'gift';
        return 'star';
    };
@endphp

<style>
    .vip-catalog-page { --vip-teal:#0f6262; --vip-teal-dark:#0a4549; --vip-gold:#c79b45; --vip-ink:#203238; --vip-muted:#718187; --vip-line:#e6edef; color:var(--vip-ink); }
    .vip-catalog-page .vip-hero { position:relative; overflow:hidden; border-radius:22px; padding:28px 30px; margin-bottom:22px; color:#fff; background:linear-gradient(120deg,#0a4549 0%,#0f6262 58%,#267d76 100%); box-shadow:0 14px 32px rgba(9,66,69,.14); }
    .vip-catalog-page .vip-hero:after { content:""; position:absolute; width:250px; height:250px; border:1px solid rgba(255,255,255,.12); border-radius:50%; left:-70px; top:-140px; box-shadow:0 0 0 28px rgba(255,255,255,.035),0 0 0 58px rgba(255,255,255,.025); pointer-events:none; }
    .vip-catalog-page .vip-hero h1 { color:#fff; font-size:24px; font-weight:800; margin:0 0 7px; }
    .vip-catalog-page .vip-hero p { color:rgba(255,255,255,.76); margin:0; line-height:1.8; font-size:13px; }
    .vip-catalog-page .vip-hero-actions { display:flex; flex-wrap:wrap; gap:9px; justify-content:flex-end; }
    .vip-catalog-page .vip-btn { border:0; border-radius:11px; padding:10px 15px; font-weight:700; font-size:12px; transition:transform .18s ease,box-shadow .18s ease; }
    .vip-catalog-page .vip-btn:hover { transform:translateY(-1px); }
    .vip-catalog-page .vip-btn-light { color:var(--vip-teal-dark); background:#fff; }
    .vip-catalog-page .vip-btn-gold { color:#2e281b; background:#e6c47c; box-shadow:0 6px 16px rgba(0,0,0,.12); }
    .vip-catalog-page .vip-btn-soft { color:var(--vip-teal); background:#eaf4f3; border:1px solid #d6e9e7; }
    .vip-catalog-page .vip-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:13px; margin-bottom:18px; }
    .vip-catalog-page .vip-stat { display:flex; align-items:center; gap:13px; border:1px solid var(--vip-line); border-radius:16px; background:#fff; padding:16px 18px; box-shadow:0 5px 18px rgba(25,53,58,.035); }
    .vip-catalog-page .vip-stat-icon { width:42px; height:42px; display:grid; place-items:center; border-radius:13px; font-size:20px; flex:0 0 42px; }
    .vip-catalog-page .vip-stat-icon.is-active { color:#17775e; background:#e7f5ef; }
    .vip-catalog-page .vip-stat-icon.is-total { color:#326a85; background:#eaf3f8; }
    .vip-catalog-page .vip-stat-icon.is-archived { color:#9a6b22; background:#fbf2df; }
    .vip-catalog-page .vip-stat-label { color:var(--vip-muted); font-size:11px; margin-bottom:3px; }
    .vip-catalog-page .vip-stat-value { color:var(--vip-ink); font-size:23px; line-height:1.15; font-weight:800; }
    .vip-catalog-page .vip-note { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; border:1px solid #dcebe9; border-radius:13px; color:#416265; background:#f3f9f8; font-size:12px; line-height:1.8; margin-bottom:18px; }
    .vip-catalog-page .vip-alert { border:0; border-radius:12px; padding:13px 16px; font-size:13px; }
    .vip-catalog-page .vip-section-title { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:23px 0 12px; }
    .vip-catalog-page .vip-section-title h3 { margin:0; color:var(--vip-ink); font-size:16px; font-weight:800; }
    .vip-catalog-page .vip-section-title p { margin:4px 0 0; color:var(--vip-muted); font-size:11px; }
    .vip-catalog-page .vip-save-button { border:0; color:#fff; background:var(--vip-teal); border-radius:11px; padding:11px 18px; font-weight:800; font-size:12px; box-shadow:0 6px 15px rgba(15,98,98,.16); }
    .vip-catalog-page .vip-service-list { display:flex; flex-direction:column; gap:11px; }
    .vip-catalog-page .vip-service-entry { display:grid; grid-template-columns:64px minmax(0,1fr); gap:10px; align-items:stretch; }
    .vip-catalog-page .vip-order-tools { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:7px; padding:9px 5px; border:1px solid var(--vip-line); border-radius:15px; background:#fbfdfd; }
    .vip-catalog-page .vip-order-number { color:var(--vip-teal); font-size:19px; font-weight:900; line-height:1; }
    .vip-catalog-page .vip-order-caption { color:var(--vip-muted); font-size:9px; font-weight:700; }
    .vip-catalog-page .vip-order-tools button { width:32px; height:30px; display:grid; place-items:center; padding:0; color:var(--vip-teal); background:#fff; border:1px solid #dfe9e9; border-radius:9px; }
    .vip-catalog-page .vip-order-tools button:hover:not(:disabled) { color:#fff; background:var(--vip-teal); border-color:var(--vip-teal); }
    .vip-catalog-page .vip-order-tools button:disabled { cursor:not-allowed; opacity:.32; }
    .vip-catalog-page .vip-service-fold { border:1px solid var(--vip-line); border-radius:16px; overflow:hidden; background:#fff; box-shadow:0 5px 18px rgba(25,53,58,.035); }
    .vip-catalog-page .vip-service-fold[open] { border-color:#b9d7d4; box-shadow:0 8px 24px rgba(15,98,98,.07); }
    .vip-catalog-page .vip-service-fold > summary { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px 19px; cursor:pointer; outline:none; }
    .vip-catalog-page .vip-service-fold > summary::-webkit-details-marker { display:none; }
    .vip-catalog-page .vip-summary-main { display:flex; align-items:center; gap:13px; min-width:0; }
    .vip-catalog-page .vip-service-icon { width:43px; height:43px; flex:0 0 43px; display:grid; place-items:center; border-radius:13px; color:var(--vip-gold); background:#fbf5e9; font-size:21px; }
    .vip-catalog-page .vip-service-name { color:var(--vip-ink); font-size:14px; font-weight:800; margin:0 0 4px; overflow-wrap:anywhere; }
    .vip-catalog-page .vip-service-code { color:var(--vip-muted); font-size:10px; }
    .vip-catalog-page .vip-summary-meta { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:7px; }
    .vip-catalog-page .vip-chip { display:inline-flex; align-items:center; gap:5px; padding:5px 9px; border-radius:30px; font-size:10px; font-weight:800; white-space:nowrap; }
    .vip-catalog-page .vip-chip-active { color:#187251; background:#e8f6ee; }
    .vip-catalog-page .vip-chip-off { color:#95651b; background:#fbf2df; }
    .vip-catalog-page .vip-chip-archived { color:#68757a; background:#edf0f1; }
    .vip-catalog-page .vip-chip-price { color:#62502b; background:#fbf5e9; }
    .vip-catalog-page .vip-chip-orders { color:#5f6c72; background:#f0f4f5; }
    .vip-catalog-page .vip-expand-icon { color:#799094; font-size:17px; transition:transform .18s ease; }
    .vip-catalog-page details[open] > summary .vip-expand-icon { transform:rotate(180deg); }
    .vip-catalog-page .vip-service-content { padding:4px 19px 18px; border-top:1px solid #edf2f3; }
    .vip-catalog-page .vip-form-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:12px; padding-top:16px; }
    .vip-catalog-page .vip-field { min-width:0; margin:0; }
    .vip-catalog-page .vip-field label { display:block; color:#4c6065; font-size:11px; font-weight:800; margin-bottom:6px; }
    .vip-catalog-page .vip-field .form-control { min-height:42px; border:1px solid #dfe8ea; border-radius:10px; color:var(--vip-ink); font-size:12px; box-shadow:none; }
    .vip-catalog-page .vip-field .form-control:focus { border-color:#6ea9a3; box-shadow:0 0 0 3px rgba(15,98,98,.08); }
    .vip-catalog-page .vip-field textarea.form-control { min-height:96px; }
    .vip-catalog-page .vip-subfold { margin-top:12px; border:1px solid #e9eff0; border-radius:12px; background:#fcfdfd; }
    .vip-catalog-page .vip-subfold > summary { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; color:#38545a; font-size:12px; font-weight:800; cursor:pointer; }
    .vip-catalog-page .vip-subfold > summary::-webkit-details-marker { display:none; }
    .vip-catalog-page .vip-subfold-content { padding:0 14px 14px; }
    .vip-catalog-page .vip-feature-row { margin:0 0 9px; padding:12px; border:1px solid #e8eff0; border-radius:11px; background:#fff; }
    .vip-catalog-page .vip-feature-row:last-child { margin-bottom:0; }
    .vip-catalog-page .vip-feature-row .form-group { margin-bottom:8px; }
    .vip-catalog-page .vip-feature-label { color:#607278; font-size:10px; font-weight:700; margin-bottom:5px; display:block; }
    .vip-catalog-page .vip-small-btn { border-radius:9px; font-size:11px; padding:7px 11px; font-weight:700; }
    .vip-catalog-page .vip-switches { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; padding-top:14px; }
    .vip-catalog-page .vip-switch { height:100%; display:flex; align-items:flex-start; gap:8px; padding:12px; border:1px solid #e8eff0; border-radius:11px; background:#fff; color:#42585d; font-size:11px; line-height:1.7; }
    .vip-catalog-page .vip-switch input { margin-top:4px; accent-color:var(--vip-teal); }
    .vip-catalog-page .vip-service-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid #edf2f3; }
    .vip-catalog-page .vip-service-footer small { color:var(--vip-muted); font-size:10px; }
    .vip-catalog-page .vip-empty { color:var(--vip-muted); text-align:center; padding:30px; border:1px dashed #d7e3e5; border-radius:15px; }
    .vip-catalog-page .vip-add-fold { border:1px solid #dce8e6; border-radius:17px; overflow:hidden; background:#fff; box-shadow:0 8px 25px rgba(15,98,98,.045); }
    .vip-catalog-page .vip-add-fold > summary { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:12px; cursor:pointer; padding:18px 20px; background:linear-gradient(100deg,#f4faf9,#fff); color:var(--vip-teal-dark); font-weight:800; }
    .vip-catalog-page .vip-add-fold > summary::-webkit-details-marker { display:none; }
    .vip-catalog-page .vip-add-content { padding:18px 20px 20px; border-top:1px solid #e9f0f0; }
    .vip-catalog-page .vip-bottom-save { display:flex; justify-content:flex-end; padding:15px 0 4px; }
    @media (max-width: 767px) {
        .vip-catalog-page .vip-hero { padding:22px 18px; }
        .vip-catalog-page .vip-hero h1 { font-size:20px; }
        .vip-catalog-page .vip-hero-actions { justify-content:flex-start; margin-top:17px; }
        .vip-catalog-page .vip-stats { grid-template-columns:1fr; gap:8px; }
        .vip-catalog-page .vip-stat { padding:12px 14px; }
        .vip-catalog-page .vip-service-entry { grid-template-columns:1fr; gap:6px; }
        .vip-catalog-page .vip-order-tools { flex-direction:row; justify-content:flex-start; padding:7px 10px; }
        .vip-catalog-page .vip-order-number { font-size:15px; }
        .vip-catalog-page .vip-order-caption { margin-left:5px; }
        .vip-catalog-page .vip-service-fold > summary { align-items:flex-start; padding:13px; }
        .vip-catalog-page .vip-summary-meta { max-width:125px; }
        .vip-catalog-page .vip-chip-orders { display:none; }
        .vip-catalog-page .vip-form-grid { gap:9px; }
        .vip-catalog-page .vip-switches { grid-template-columns:1fr; }
        .vip-catalog-page .vip-service-content { padding:3px 12px 14px; }
    }
</style>

<div class="vip-catalog-page" dir="rtl">
    <div class="vip-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1><i class="las la-crown mr-1"></i> إدارة خدمات VIP الإضافية</h1>
                <p>واجهة موحّدة وبسيطة للتحكم بالخدمات التي تظهر في تطبيق وصال، مع ترتيب مباشر وواضح دون ازدحام الحقول.</p>
            </div>
            <div class="col-lg-4">
                <div class="vip-hero-actions">
                    <a href="#vip-service-list" class="vip-btn vip-btn-light"><i class="las la-list-ol"></i> قائمة الخدمات</a>
                    <a href="#vip-add-service" class="vip-btn vip-btn-gold"><i class="las la-plus"></i> إضافة خدمة</a>
                    <button type="submit" form="vip-services-form" class="vip-btn vip-btn-light"><i class="las la-save"></i> حفظ التعديلات</button>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success vip-alert">{{ session('success') }}</div>
    @endif
    @if(session('danger'))
        <div class="alert alert-danger vip-alert">{{ session('danger') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning vip-alert">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger vip-alert">
            <strong>تعذّر حفظ بعض الحقول. راجع الملاحظات التالية:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="vip-stats">
        <div class="vip-stat">
            <div class="vip-stat-icon is-active"><i class="las la-check-circle"></i></div>
            <div><div class="vip-stat-label">خدمات مفعّلة في التطبيق</div><div class="vip-stat-value">{{ $activeCount }}</div></div>
        </div>
        <div class="vip-stat">
            <div class="vip-stat-icon is-total"><i class="las la-layer-group"></i></div>
            <div><div class="vip-stat-label">إجمالي الخدمات</div><div class="vip-stat-value">{{ count($services) }}</div></div>
        </div>
        <div class="vip-stat">
            <div class="vip-stat-icon is-archived"><i class="las la-archive"></i></div>
            <div><div class="vip-stat-label">خدمات مؤرشفة</div><div class="vip-stat-value">{{ $archivedCount }}</div></div>
        </div>
    </div>

    <div class="vip-note">
        <i class="las la-info-circle mt-1"></i>
        <div><strong>طريقة الترتيب:</strong> استخدم السهمين بجانب كل خدمة لتحريكها للأعلى أو للأسفل. الترتيب الأعلى يظهر أولاً في التطبيق، ويُحفظ تلقائياً بأرقام متسلسلة عند الضغط على «حفظ التعديلات». الخدمات المعطّلة لا تظهر للمستخدم، لكنها تبقى هنا لإدارتها.</div>
    </div>

    <div class="vip-section-title" id="vip-service-list">
        <div>
            <h3>كتالوج الخدمات</h3>
            <p>اضغط على بطاقة الخدمة لفتح تفاصيلها. تبقى جميع البطاقات مغلقة افتراضياً لتسهيل التنقل.</p>
        </div>
        <button type="submit" form="vip-services-form" class="vip-save-button"><i class="las la-save"></i> حفظ التعديلات</button>
    </div>

    <form id="vip-services-form" action="{{ route('admin.vip-services.catalog.update') }}" method="POST">
        @csrf
        @if(count($services))
            <div id="vip-service-list-items" class="vip-service-list">
                @foreach($services as $index => $service)
                    @php
                        $arItems = $service['included_ar'] ?? [];
                        $enItems = $service['included_en'] ?? [];
                        $featureRows = [];
                        $featureCount = max(count($arItems), count($enItems));
                        for ($featureIndex = 0; $featureIndex < $featureCount; $featureIndex++) {
                            $arItem = $arItems[$featureIndex] ?? '';
                            $enItem = $enItems[$featureIndex] ?? '';
                            $featureTitleAr = is_array($arItem) ? ($arItem['title'] ?? '') : $arItem;
                            $featureTitleEn = is_array($enItem) ? ($enItem['title'] ?? '') : $enItem;
                            $featureRows[] = [
                                'title_ar' => $featureTitleAr,
                                'title_en' => $featureTitleEn,
                                'icon_key' => is_array($arItem)
                                    ? ($arItem['icon_key'] ?? 'star')
                                    : (is_array($enItem)
                                        ? ($enItem['icon_key'] ?? $inferFeatureIcon($featureTitleAr ?: $featureTitleEn))
                                        : $inferFeatureIcon($featureTitleAr ?: $featureTitleEn)),
                            ];
                        }
                        $featureRows = old('services.'.$index.'.features', $featureRows);
                        $featureRows = is_array($featureRows) ? array_values($featureRows) : [];
                        $serviceArchived = !empty($service['archived']);
                        $serviceActive = old('services.'.$index.'.active', (!empty($service['active']) && !$serviceArchived) ? '1' : '0') == '1';
                        $allowExternal = old('services.'.$index.'.allow_external_contact', !empty($service['allow_external_contact']) ? '1' : '0') == '1';
                        $showSpecialThanks = old('services.'.$index.'.show_special_thanks', !empty($service['show_special_thanks']) ? '1' : '0') == '1';
                    @endphp

                    <div class="vip-service-entry" data-service-entry>
                        <div class="vip-order-tools" aria-label="أدوات ترتيب الخدمة">
                            <div class="vip-order-number" data-order-number>{{ $loop->iteration }}</div>
                            <div class="vip-order-caption">الترتيب</div>
                            <button type="button" data-move-up aria-label="رفع الخدمة"><i class="las la-chevron-up"></i></button>
                            <button type="button" data-move-down aria-label="خفض الخدمة"><i class="las la-chevron-down"></i></button>
                        </div>

                        <details class="vip-service-fold">
                            <summary>
                                <div class="vip-summary-main">
                                    <div class="vip-service-icon"><i class="las {{ $iconClasses[$service['icon_key']] ?? 'la-star' }}"></i></div>
                                    <div class="min-w-0">
                                        <div class="vip-service-name">{{ $service['title_ar'] ?: $service['service_type'] }}</div>
                                        <div class="vip-service-code">{{ $service['service_type'] }} · رقم داخلي #{{ $service['id'] ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="vip-summary-meta">
                                    @if($serviceArchived)
                                        <span class="vip-chip vip-chip-archived">مؤرشفة</span>
                                    @elseif($serviceActive)
                                        <span class="vip-chip vip-chip-active">مفعّلة</span>
                                    @else
                                        <span class="vip-chip vip-chip-off">متوقفة</span>
                                    @endif
                                    <span class="vip-chip vip-chip-price">{{ number_format((float) $service['price'], 2) }} $</span>
                                    <span class="vip-chip vip-chip-orders">{{ $service['orders_count'] ?? 0 }} طلب</span>
                                    <i class="las la-chevron-down vip-expand-icon"></i>
                                </div>
                            </summary>

                            <div class="vip-service-content">
                                <input type="hidden" name="services[{{ $index }}][service_type]" value="{{ $service['service_type'] }}">
                                <input type="hidden" name="services[{{ $index }}][sort_order]" value="{{ $service['sort_order'] ?? (($index + 1) * 10) }}" data-sort-order-field>
                                <input type="hidden" name="services[{{ $index }}][active]" value="0">

                                <div class="vip-form-grid">
                                    <div class="vip-field" style="grid-column:span 6">
                                        <label>اسم الخدمة بالعربية *</label>
                                        <input type="text" name="services[{{ $index }}][title_ar]" class="form-control" maxlength="180" required value="{{ old('services.'.$index.'.title_ar', $service['title_ar']) }}">
                                    </div>
                                    <div class="vip-field" style="grid-column:span 6">
                                        <label>اسم الخدمة بالإنجليزية</label>
                                        <input type="text" name="services[{{ $index }}][title_en]" class="form-control" maxlength="180" value="{{ old('services.'.$index.'.title_en', $service['title_en']) }}">
                                    </div>
                                    <div class="vip-field" style="grid-column:span 4">
                                        <label>السعر بالدولار *</label>
                                        <input type="number" name="services[{{ $index }}][price]" class="form-control" min="0.01" max="100000" step="0.01" required value="{{ old('services.'.$index.'.price', $service['price']) }}">
                                    </div>
                                    <div class="vip-field" style="grid-column:span 8">
                                        <label>الأيقونة الرئيسية</label>
                                        <select name="services[{{ $index }}][icon_key]" class="form-control" required>
                                            @foreach($iconOptions as $key => $label)
                                                <option value="{{ $key }}" {{ old('services.'.$index.'.icon_key', $service['icon_key']) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <details class="vip-subfold">
                                    <summary><span><i class="las la-align-right mr-1"></i> النصوص والتفاصيل المعروضة</span><i class="las la-chevron-down vip-expand-icon"></i></summary>
                                    <div class="vip-subfold-content">
                                        <div class="row pt-3">
                                            <div class="col-lg-6 vip-field">
                                                <label>التفاصيل بالعربية *</label>
                                                <textarea name="services[{{ $index }}][promo_ar]" class="form-control" rows="3" maxlength="1500" required>{{ old('services.'.$index.'.promo_ar', $service['promo_ar']) }}</textarea>
                                            </div>
                                            <div class="col-lg-6 vip-field">
                                                <label>Details in English</label>
                                                <textarea name="services[{{ $index }}][promo_en]" class="form-control" rows="3" maxlength="1500">{{ old('services.'.$index.'.promo_en', $service['promo_en']) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <details class="vip-subfold">
                                    <summary><span><i class="las la-list-ul mr-1"></i> مزايا الخدمة وبنودها <span class="text-muted font-weight-normal">({{ count($featureRows) }})</span></span><i class="las la-chevron-down vip-expand-icon"></i></summary>
                                    <div class="vip-subfold-content">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap pt-3 pb-2">
                                            <small class="text-muted">كل ميزة لها عنوان عربي وإنجليزي وأيقونة مستقلة.</small>
                                            <button type="button" class="btn vip-btn-soft vip-small-btn mt-2 mt-sm-0" data-add-feature data-target="service-features-{{ $index }}" data-prefix="services[{{ $index }}][features]"><i class="las la-plus"></i> إضافة بند</button>
                                        </div>
                                        <div id="service-features-{{ $index }}" data-feature-list data-next-index="{{ count($featureRows) }}">
                                            @foreach($featureRows as $featureIndex => $feature)
                                                <div class="row align-items-end vip-feature-row">
                                                    <div class="col-md-5 form-group">
                                                        <label class="vip-feature-label">الميزة بالعربية</label>
                                                        <input type="text" name="services[{{ $index }}][features][{{ $featureIndex }}][title_ar]" class="form-control" maxlength="180" value="{{ $feature['title_ar'] }}">
                                                    </div>
                                                    <div class="col-md-5 form-group">
                                                        <label class="vip-feature-label">Feature in English</label>
                                                        <input type="text" name="services[{{ $index }}][features][{{ $featureIndex }}][title_en]" class="form-control" maxlength="180" value="{{ $feature['title_en'] }}">
                                                    </div>
                                                    <div class="col-md-2 form-group">
                                                        <label class="vip-feature-label">الأيقونة</label>
                                                        <select name="services[{{ $index }}][features][{{ $featureIndex }}][icon_key]" class="form-control">
                                                            @foreach($iconOptions as $key => $label)
                                                                <option value="{{ $key }}" {{ $feature['icon_key'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 text-left mb-1">
                                                        <button type="button" class="btn btn-sm btn-outline-danger vip-small-btn" data-remove-feature><i class="las la-trash"></i> حذف البند</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>

                                <details class="vip-subfold">
                                    <summary><span><i class="las la-sliders-h mr-1"></i> خيارات الطلب والتواصل</span><i class="las la-chevron-down vip-expand-icon"></i></summary>
                                    <div class="vip-subfold-content">
                                        <div class="vip-switches">
                                            <label class="vip-switch">
                                                <input type="checkbox" name="services[{{ $index }}][active]" value="1" {{ $serviceActive ? 'checked' : '' }} {{ $serviceArchived ? 'disabled' : '' }}>
                                                <span><strong>إظهار الخدمة</strong><br>تقبل طلبات جديدة وتظهر للمستخدمين.</span>
                                            </label>
                                            <input type="hidden" name="services[{{ $index }}][allow_external_contact]" value="{{ $serviceArchived ? (!empty($service['allow_external_contact']) ? '1' : '0') : '0' }}">
                                            <label class="vip-switch">
                                                <input type="checkbox" name="services[{{ $index }}][allow_external_contact]" value="1" {{ $allowExternal ? 'checked' : '' }} {{ $serviceArchived ? 'disabled' : '' }}>
                                                <span><strong>تواصل خارجي</strong><br>إظهار حقول واتساب أو رابط خارجي.</span>
                                            </label>
                                            <input type="hidden" name="services[{{ $index }}][show_special_thanks]" value="{{ $serviceArchived ? (!empty($service['show_special_thanks']) ? '1' : '0') : '0' }}">
                                            <label class="vip-switch">
                                                <input type="checkbox" name="services[{{ $index }}][show_special_thanks]" value="1" {{ $showSpecialThanks ? 'checked' : '' }} {{ $serviceArchived ? 'disabled' : '' }}>
                                                <span><strong>رسالة شكر خاصة</strong><br>عرض بروتوكول الشكر بعد تسجيل الطلب.</span>
                                            </label>
                                        </div>
                                    </div>
                                </details>

                                <div class="vip-service-footer">
                                    <small>الرمز الداخلي ثابت لحماية ربط الطلبات السابقة: <code>{{ $service['service_type'] }}</code></small>
                                    @if($serviceArchived)
                                        <button type="submit" class="btn btn-outline-success vip-small-btn" formaction="{{ route('admin.vip-services.catalog.restore', $service['service_type']) }}" formmethod="POST" onclick="return confirm('إعادة تفعيل هذه الخدمة؟');"><i class="las la-undo"></i> استعادة الخدمة</button>
                                    @else
                                        <button type="submit" class="btn btn-outline-danger vip-small-btn" formaction="{{ route('admin.vip-services.catalog.remove', $service['service_type']) }}" formmethod="POST" onclick="return confirm('إخفاء أو حذف هذه الخدمة؟ ستبقى الطلبات السابقة محفوظة.');"><i class="las la-eye-slash"></i> إخفاء / حذف</button>
                                    @endif
                                </div>
                            </div>
                        </details>
                    </div>
                @endforeach
            </div>
        @else
            <div class="vip-empty">لا توجد خدمات في الكتالوج حالياً. استخدم «إضافة خدمة» لإنشاء أول خدمة.</div>
        @endif

        <div class="vip-bottom-save">
            <button type="submit" class="vip-save-button"><i class="las la-save"></i> حفظ جميع التغييرات</button>
        </div>
    </form>

    <div class="vip-section-title mt-4" id="vip-add-service">
        <div><h3>إضافة خدمة جديدة</h3><p>يظهر النموذج عند فتح القائمة فقط؛ لا يشغل مساحة الصفحة أثناء إدارة الخدمات الحالية.</p></div>
    </div>

    @php
        $newFeatureRows = old('features', []);
        $newFeatureRows = is_array($newFeatureRows) ? $newFeatureRows : [];
        $newFeatureNextIndex = count($newFeatureRows) ? max(array_keys($newFeatureRows)) + 1 : 0;
    @endphp
    <details class="vip-add-fold">
        <summary>
            <span><i class="las la-plus-circle mr-2"></i> افتح نموذج إضافة خدمة</span>
            <span class="vip-chip vip-chip-active">إضافة إلى التطبيق <i class="las la-chevron-down vip-expand-icon"></i></span>
        </summary>
        <div class="vip-add-content">
            <form action="{{ route('admin.vip-services.catalog.add') }}" method="POST">
                @csrf
                <div class="vip-form-grid pt-0">
                    <div class="vip-field" style="grid-column:span 6">
                        <label>اسم الخدمة بالعربية *</label>
                        <input type="text" name="title_ar" class="form-control" maxlength="180" required value="{{ old('title_ar') }}">
                    </div>
                    <div class="vip-field" style="grid-column:span 6">
                        <label>اسم الخدمة بالإنجليزية</label>
                        <input type="text" name="title_en" class="form-control" maxlength="180" value="{{ old('title_en') }}">
                    </div>
                    <div class="vip-field" style="grid-column:span 4">
                        <label>السعر بالدولار *</label>
                        <input type="number" name="price" class="form-control" min="0.01" max="100000" step="0.01" required value="{{ old('price') }}">
                    </div>
                    <div class="vip-field" style="grid-column:span 4">
                        <label>ترتيب البداية</label>
                        <input type="number" name="sort_order" class="form-control" min="0" max="9999" step="10" value="{{ old('sort_order', $nextSortOrder) }}" required>
                        <small class="text-muted">رقم أصغر يجعل الخدمة أقرب إلى أول القائمة.</small>
                    </div>
                    <div class="vip-field" style="grid-column:span 4">
                        <label>الأيقونة الرئيسية</label>
                        <select name="icon_key" class="form-control" required>
                            @foreach($iconOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('icon_key', 'star') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="vip-field" style="grid-column:span 6">
                        <label>التفاصيل بالعربية *</label>
                        <textarea name="promo_ar" class="form-control" rows="3" maxlength="1500" required>{{ old('promo_ar') }}</textarea>
                    </div>
                    <div class="vip-field" style="grid-column:span 6">
                        <label>Details in English</label>
                        <textarea name="promo_en" class="form-control" rows="3" maxlength="1500">{{ old('promo_en') }}</textarea>
                    </div>
                </div>

                <details class="vip-subfold mt-3">
                    <summary><span><i class="las la-list-ul mr-1"></i> مزايا الخدمة الجديدة (اختياري)</span><i class="las la-chevron-down vip-expand-icon"></i></summary>
                    <div class="vip-subfold-content">
                        <div class="d-flex align-items-center justify-content-between flex-wrap pt-3 pb-2">
                            <small class="text-muted">يمكنك إضافة حتى 30 ميزة لكل خدمة.</small>
                            <button type="button" class="btn vip-btn-soft vip-small-btn mt-2 mt-sm-0" data-add-feature data-target="new-service-features" data-prefix="features"><i class="las la-plus"></i> إضافة ميزة</button>
                        </div>
                        <div id="new-service-features" data-feature-list data-next-index="{{ $newFeatureNextIndex }}">
                            @foreach($newFeatureRows as $featureIndex => $feature)
                                <div class="row align-items-end vip-feature-row">
                                    <div class="col-md-5 form-group">
                                        <label class="vip-feature-label">الميزة بالعربية</label>
                                        <input type="text" name="features[{{ $featureIndex }}][title_ar]" class="form-control" maxlength="180" value="{{ $feature['title_ar'] ?? '' }}">
                                    </div>
                                    <div class="col-md-5 form-group">
                                        <label class="vip-feature-label">Feature in English</label>
                                        <input type="text" name="features[{{ $featureIndex }}][title_en]" class="form-control" maxlength="180" value="{{ $feature['title_en'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label class="vip-feature-label">الأيقونة</label>
                                        <select name="features[{{ $featureIndex }}][icon_key]" class="form-control">
                                            @foreach($iconOptions as $key => $label)
                                                <option value="{{ $key }}" {{ ($feature['icon_key'] ?? 'star') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 text-left mb-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger vip-small-btn" data-remove-feature><i class="las la-trash"></i> حذف الميزة</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </details>

                <div class="vip-switches mt-2">
                    <label class="vip-switch">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" value="1" {{ old('active', '1') == '1' ? 'checked' : '' }}>
                        <span><strong>تفعيل مباشرة</strong><br>إظهار الخدمة فور إضافتها.</span>
                    </label>
                    <label class="vip-switch">
                        <input type="hidden" name="allow_external_contact" value="0">
                        <input type="checkbox" name="allow_external_contact" value="1" {{ old('allow_external_contact', '0') == '1' ? 'checked' : '' }}>
                        <span><strong>تواصل خارجي</strong><br>طلب واتساب أو رابط خارجي.</span>
                    </label>
                    <label class="vip-switch">
                        <input type="hidden" name="show_special_thanks" value="0">
                        <input type="checkbox" name="show_special_thanks" value="1" {{ old('show_special_thanks', '0') == '1' ? 'checked' : '' }}>
                        <span><strong>رسالة شكر</strong><br>عرض بروتوكول شكر بعد الطلب.</span>
                    </label>
                </div>

                <div class="vip-bottom-save">
                    <button type="submit" class="btn btn-success vip-btn"><i class="las la-plus"></i> إضافة الخدمة إلى الكتالوج</button>
                </div>
            </form>
        </div>
    </details>
</div>

<template id="vip-feature-row-template">
    <div class="row align-items-end vip-feature-row">
        <div class="col-md-5 form-group">
            <label class="vip-feature-label">الميزة بالعربية</label>
            <input type="text" class="form-control" maxlength="180" data-feature-field="title_ar" placeholder="اكتب الميزة بالعربية">
        </div>
        <div class="col-md-5 form-group">
            <label class="vip-feature-label">Feature in English</label>
            <input type="text" class="form-control" maxlength="180" data-feature-field="title_en" placeholder="Write the feature in English">
        </div>
        <div class="col-md-2 form-group">
            <label class="vip-feature-label">الأيقونة</label>
            <select class="form-control" data-feature-field="icon_key">
                @foreach($iconOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 text-left mb-1">
            <button type="button" class="btn btn-sm btn-outline-danger vip-small-btn" data-remove-feature><i class="las la-trash"></i> حذف البند</button>
        </div>
    </div>
</template>

<script>
(function () {
    const serviceList = document.getElementById('vip-service-list-items');

    function syncServiceOrder() {
        if (!serviceList) return;
        const entries = Array.from(serviceList.querySelectorAll(':scope > [data-service-entry]'));
        entries.forEach(function (entry, index) {
            const position = index + 1;
            const sortField = entry.querySelector('[data-sort-order-field]');
            const numberLabel = entry.querySelector('[data-order-number]');
            const upButton = entry.querySelector('[data-move-up]');
            const downButton = entry.querySelector('[data-move-down]');

            if (sortField) sortField.value = String(position * 10);
            if (numberLabel) numberLabel.textContent = String(position);
            if (upButton) upButton.disabled = index === 0;
            if (downButton) downButton.disabled = index === entries.length - 1;
        });
    }

    document.addEventListener('click', function (event) {
        const moveUp = event.target.closest('[data-move-up]');
        if (moveUp && serviceList) {
            const entry = moveUp.closest('[data-service-entry]');
            const previous = entry ? entry.previousElementSibling : null;
            if (entry && previous && previous.matches('[data-service-entry]')) {
                serviceList.insertBefore(entry, previous);
                syncServiceOrder();
            }
            return;
        }

        const moveDown = event.target.closest('[data-move-down]');
        if (moveDown && serviceList) {
            const entry = moveDown.closest('[data-service-entry]');
            const next = entry ? entry.nextElementSibling : null;
            if (entry && next && next.matches('[data-service-entry]')) {
                serviceList.insertBefore(next, entry);
                syncServiceOrder();
            }
            return;
        }

        const addButton = event.target.closest('[data-add-feature]');
        if (addButton) {
            const list = document.getElementById(addButton.getAttribute('data-target'));
            if (!list) return;
            if (list.querySelectorAll('.vip-feature-row').length >= 30) {
                window.alert('يمكن إضافة 30 بنداً كحد أقصى لكل خدمة.');
                return;
            }
            const prefix = addButton.getAttribute('data-prefix');
            const index = Number(list.dataset.nextIndex || 0);
            list.dataset.nextIndex = String(index + 1);
            const template = document.getElementById('vip-feature-row-template');
            const row = template.content.cloneNode(true);
            row.querySelectorAll('[data-feature-field]').forEach(function (field) {
                field.name = prefix + '[' + index + '][' + field.getAttribute('data-feature-field') + ']';
            });
            list.appendChild(row);
            return;
        }

        const removeButton = event.target.closest('[data-remove-feature]');
        if (removeButton) {
            const row = removeButton.closest('.vip-feature-row');
            if (row) row.remove();
        }
    });

    syncServiceOrder();
})();
</script>
@endsection
