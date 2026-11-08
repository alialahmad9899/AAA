@extends('admin.layouts.app')

@section('content')
@php
    $activeCount = count(array_filter($services, function ($service) {
        return !empty($service['active']) && empty($service['archived']);
    }));
    $archivedCount = count(array_filter($services, function ($service) {
        return !empty($service['archived']);
    }));
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

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3">إدارة كتالوج خدمات VIP</h1>
            <p class="text-muted mb-0">تحكم كامل بالخدمات التي تظهر في تطبيق وصال: الاسم والتفاصيل والسعر والأيقونة والمزايا وترتيب كل بند.</p>
        </div>
        <div class="col-md-4 text-md-right mt-2 mt-md-0">
            <a href="#add-vip-service" class="btn btn-primary mr-1"><i class="las la-plus"></i> إضافة خدمة جديدة</a>
            <a href="{{ route('admin.vip-services.index') }}" class="btn btn-light">طلبات الخدمات</a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="إغلاق"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
@if(session('danger'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('danger') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="إغلاق"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="إغلاق"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="row mb-4">
    <div class="col-md-4 mb-2"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center"><div class="bg-soft-success rounded-circle p-3 mr-3"><i class="las la-check-circle text-success la-2x"></i></div><div><div class="text-muted small">الخدمات الظاهرة في التطبيق</div><div class="h3 mb-0">{{ $activeCount }}</div></div></div></div></div>
    <div class="col-md-4 mb-2"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center"><div class="bg-soft-secondary rounded-circle p-3 mr-3"><i class="las la-layer-group text-secondary la-2x"></i></div><div><div class="text-muted small">إجمالي الخدمات</div><div class="h3 mb-0">{{ count($services) }}</div></div></div></div></div>
    <div class="col-md-4 mb-2"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center"><div class="bg-soft-warning rounded-circle p-3 mr-3"><i class="las la-archive text-warning la-2x"></i></div><div><div class="text-muted small">الخدمات المؤرشفة</div><div class="h3 mb-0">{{ $archivedCount }}</div></div></div></div></div>
</div>

<div class="alert alert-info">
    <i class="las la-info-circle mr-1"></i>
    كل ميزة تُحفظ كبند مستقل مع عنوان عربي وإنجليزي وأيقونة. الخدمة المؤرشفة تختفي من التطبيق، لكن تبقى بيانات الطلبات السابقة محفوظة.
</div>
@if($errors->any())
    <div class="alert alert-danger"><strong>يرجى مراجعة البيانات:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<template id="vip-feature-row-template">
    <div class="row align-items-end vip-feature-row border rounded p-2 mb-2">
        <div class="col-12 mb-2"><span class="badge badge-light" data-feature-number>بند جديد</span></div>
        <div class="col-md-5 form-group mb-2">
            <label>الميزة بالعربية</label>
            <input type="text" class="form-control" maxlength="180" data-feature-field="title_ar" placeholder="اكتب الميزة بالعربية">
        </div>
        <div class="col-md-5 form-group mb-2">
            <label>Feature in English</label>
            <input type="text" class="form-control" maxlength="180" data-feature-field="title_en" placeholder="Write the feature in English">
        </div>
        <div class="col-md-2 form-group mb-2">
            <label>أيقونة البند</label>
            <select class="form-control" data-feature-field="icon_key">
                @foreach($iconOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 text-right">
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-feature><i class="las la-trash"></i> حذف هذا البند</button>
        </div>
    </div>
</template>

<form action="{{ route('admin.vip-services.catalog.update') }}" method="POST">
    @csrf
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

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center">
                    <span class="rounded-circle bg-soft-warning text-warning p-3 mr-3"><i class="las {{ $iconClasses[$service['icon_key']] ?? 'la-star' }} la-2x"></i></span>
                    <div>
                        <h5 class="mb-1">{{ $service['title_ar'] ?: $service['service_type'] }}</h5>
                        <small class="text-muted">{{ $service['service_type'] }} · #{{ $service['id'] ?? '-' }}</small>
                    </div>
                </div>
                <div class="mt-2 mt-md-0">
                    @if($serviceArchived)<span class="badge badge-secondary">مؤرشفة</span>
                    @elseif($serviceActive)<span class="badge badge-success">ظاهرة في التطبيق</span>
                    @else<span class="badge badge-warning">متوقفة مؤقتاً</span>@endif
                    <span class="badge badge-light ml-1">{{ $service['orders_count'] ?? 0 }} طلب سابق</span>
                </div>
            </div>
            <div class="card-body">
                <input type="hidden" name="services[{{ $index }}][service_type]" value="{{ $service['service_type'] }}">
                <input type="hidden" name="services[{{ $index }}][active]" value="0">

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>اسم الخدمة بالعربية *</label>
                        <input type="text" name="services[{{ $index }}][title_ar]" class="form-control" maxlength="180" required value="{{ old('services.'.$index.'.title_ar', $service['title_ar']) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>اسم الخدمة بالإنجليزية</label>
                        <input type="text" name="services[{{ $index }}][title_en]" class="form-control" maxlength="180" value="{{ old('services.'.$index.'.title_en', $service['title_en']) }}">
                    </div>
                    <div class="col-md-8 form-group">
                        <label>التفاصيل المختصرة بالعربية *</label>
                        <textarea name="services[{{ $index }}][promo_ar]" class="form-control" rows="3" maxlength="1500" required>{{ old('services.'.$index.'.promo_ar', $service['promo_ar']) }}</textarea>
                        <small class="text-muted">يظهر هذا النص في بطاقة الخدمة ونافذة التفاصيل بالتطبيق.</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>السعر بالدولار الأمريكي *</label>
                        <input type="number" name="services[{{ $index }}][price]" class="form-control" min="0.01" max="100000" step="0.01" required value="{{ old('services.'.$index.'.price', $service['price']) }}">
                        <div class="mt-2">
                            <label class="d-block mb-1">
                                <input type="checkbox" name="services[{{ $index }}][active]" value="1" {{ $serviceActive ? 'checked' : '' }} {{ $serviceArchived ? 'disabled' : '' }}>
                                إظهار الخدمة وقبول طلبات جديدة
                            </label>
                            <input type="hidden" name="services[{{ $index }}][allow_external_contact]" value="{{ $serviceArchived ? (!empty($service['allow_external_contact']) ? '1' : '0') : '0' }}">
                            <label class="d-block mb-1">
                                <input type="checkbox" name="services[{{ $index }}][allow_external_contact]" value="1" {{ $allowExternal ? 'checked' : '' }} {{ $serviceArchived ? 'disabled' : '' }}>
                                إظهار حقول التواصل الخارجي (واتساب / رابط)
                            </label>
                            <input type="hidden" name="services[{{ $index }}][show_special_thanks]" value="{{ $serviceArchived ? (!empty($service['show_special_thanks']) ? '1' : '0') : '0' }}">
                            <label class="d-block mb-0">
                                <input type="checkbox" name="services[{{ $index }}][show_special_thanks]" value="1" {{ $showSpecialThanks ? 'checked' : '' }} {{ $serviceArchived ? 'disabled' : '' }}>
                                عرض بروتوكول الشكر الخاص بعد تسجيل الطلب
                            </label>
                        </div>
                    </div>
                    <div class="col-md-8 form-group">
                        <label>التفاصيل المختصرة بالإنجليزية</label>
                        <textarea name="services[{{ $index }}][promo_en]" class="form-control" rows="3" maxlength="1500">{{ old('services.'.$index.'.promo_en', $service['promo_en']) }}</textarea>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>الأيقونة الرئيسية للخدمة</label>
                        <select name="services[{{ $index }}][icon_key]" class="form-control" required>
                            @foreach($iconOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('services.'.$index.'.icon_key', $service['icon_key']) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label class="mt-2">ترتيب ظهور الخدمة</label>
                        <input type="number" name="services[{{ $index }}][sort_order]" class="form-control" min="0" max="9999" required value="{{ old('services.'.$index.'.sort_order', $service['sort_order']) }}">
                    </div>
                </div>

                <div class="border-top pt-3 mt-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                        <div><h6 class="mb-1">تفاصيل ومزايا الخدمة (البنود)</h6><small class="text-muted">أضف البند بالعربية والإنجليزية واختر أيقونته بشكل مستقل.</small></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 mt-md-0" data-add-feature data-target="service-features-{{ $index }}" data-prefix="services[{{ $index }}][features]"><i class="las la-plus"></i> إضافة بند</button>
                    </div>
                    <div id="service-features-{{ $index }}" data-feature-list data-next-index="{{ count($featureRows) }}">
                        @foreach($featureRows as $featureIndex => $feature)
                            <div class="row align-items-end vip-feature-row border rounded p-2 mb-2">
                                <div class="col-12 mb-2"><span class="badge badge-light">بند {{ $featureIndex + 1 }}</span></div>
                                <div class="col-md-5 form-group mb-2">
                                    <label>الميزة بالعربية</label>
                                    <input type="text" name="services[{{ $index }}][features][{{ $featureIndex }}][title_ar]" class="form-control" maxlength="180" value="{{ $feature['title_ar'] }}">
                                </div>
                                <div class="col-md-5 form-group mb-2">
                                    <label>Feature in English</label>
                                    <input type="text" name="services[{{ $index }}][features][{{ $featureIndex }}][title_en]" class="form-control" maxlength="180" value="{{ $feature['title_en'] }}">
                                </div>
                                <div class="col-md-2 form-group mb-2">
                                    <label>أيقونة البند</label>
                                    <select name="services[{{ $index }}][features][{{ $featureIndex }}][icon_key]" class="form-control">
                                        @foreach($iconOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $feature['icon_key'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 text-right">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-feature><i class="las la-trash"></i> حذف هذا البند</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-top pt-3 mt-3">
                    <small class="text-muted d-block mb-2">النوع البرمجي ثابت لحماية سجل الطلبات: {{ $service['service_type'] }}</small>
                    @if($serviceArchived)
                        <button type="submit" class="btn btn-sm btn-outline-success" formaction="{{ route('admin.vip-services.catalog.restore', $service['service_type']) }}" formmethod="POST" onclick="return confirm('إعادة تفعيل هذه الخدمة؟');">إعادة تفعيل الخدمة</button>
                    @else
                        <button type="submit" class="btn btn-sm btn-outline-danger" formaction="{{ route('admin.vip-services.catalog.remove', $service['service_type']) }}" formmethod="POST" onclick="return confirm('إلغاء هذه الخدمة من التطبيق؟ ستبقى الطلبات السابقة محفوظة.');">إلغاء / حذف الخدمة</button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="card border-0 shadow-sm mb-5"><div class="card-body text-right"><button type="submit" class="btn btn-primary btn-lg"><i class="las la-save mr-1"></i> حفظ جميع تعديلات الكتالوج</button></div></div>
</form>

<div class="card border-0 shadow-sm mb-5" id="add-vip-service">
    <div class="card-header bg-white">
        <h4 class="h5 mb-1"><i class="las la-plus-circle text-success mr-1"></i> إضافة خدمة جديدة</h4>
        <p class="text-muted mb-0">الخدمة الجديدة ستظهر في تطبيق الموبايل بنفس بطاقة التفاصيل وخيارات الطلب والدفع.</p>
    </div>
    <div class="card-body">
        @php $newFeatureRows = old('features', []); @endphp
        <form action="{{ route('admin.vip-services.catalog.add') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 form-group"><label>اسم الخدمة بالعربية *</label><input type="text" name="title_ar" class="form-control" maxlength="180" required value="{{ old('title_ar') }}"></div>
                <div class="col-md-6 form-group"><label>اسم الخدمة بالإنجليزية</label><input type="text" name="title_en" class="form-control" maxlength="180" value="{{ old('title_en') }}"></div>
                <div class="col-md-8 form-group"><label>تفاصيل الخدمة بالعربية *</label><textarea name="promo_ar" class="form-control" rows="3" maxlength="1500" required>{{ old('promo_ar') }}</textarea></div>
                <div class="col-md-4 form-group"><label>السعر بالدولار الأمريكي *</label><input type="number" name="price" class="form-control" min="0.01" max="100000" step="0.01" required value="{{ old('price') }}"></div>
                <div class="col-md-8 form-group"><label>تفاصيل الخدمة بالإنجليزية</label><textarea name="promo_en" class="form-control" rows="3" maxlength="1500">{{ old('promo_en') }}</textarea></div>
                <div class="col-md-4 form-group">
                    <label>الأيقونة الرئيسية</label>
                    <select name="icon_key" class="form-control" required>@foreach($iconOptions as $key => $label)<option value="{{ $key }}" {{ old('icon_key', 'star') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                    <label class="mt-2">ترتيب الظهور</label><input type="number" name="sort_order" class="form-control" min="0" max="9999" value="{{ old('sort_order', 40) }}" required>
                    <div class="mt-2">
                        <input type="hidden" name="active" value="0">
                        <label class="d-block"><input type="checkbox" name="active" value="1" {{ old('active', '1') == '1' ? 'checked' : '' }}> تفعيل فور الإضافة</label>
                        <input type="hidden" name="allow_external_contact" value="0">
                        <label class="d-block"><input type="checkbox" name="allow_external_contact" value="1" {{ old('allow_external_contact', '0') == '1' ? 'checked' : '' }}> تتطلب وسيلة تواصل خارجية (واتساب / رابط)</label>
                        <input type="hidden" name="show_special_thanks" value="0">
                        <label class="d-block"><input type="checkbox" name="show_special_thanks" value="1" {{ old('show_special_thanks', '0') == '1' ? 'checked' : '' }}> عرض بروتوكول الشكر الخاص بعد الطلب</label>
                    </div>
                </div>
            </div>
            <div class="border-top pt-3 mt-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <div><h6 class="mb-1">بنود الخدمة الجديدة</h6><small class="text-muted">يمكنك إضافة عدد من البنود، ولكل بند أيقونة خاصة.</small></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 mt-md-0" data-add-feature data-target="new-service-features" data-prefix="features"><i class="las la-plus"></i> إضافة بند</button>
                </div>
                <div id="new-service-features" data-feature-list data-next-index="{{ count($newFeatureRows) }}">
                    @foreach($newFeatureRows as $featureIndex => $feature)
                        <div class="row align-items-end vip-feature-row border rounded p-2 mb-2">
                            <div class="col-12 mb-2"><span class="badge badge-light">بند {{ $featureIndex + 1 }}</span></div>
                            <div class="col-md-5 form-group mb-2">
                                <label>الميزة بالعربية</label>
                                <input type="text" name="features[{{ $featureIndex }}][title_ar]" class="form-control" maxlength="180" value="{{ $feature['title_ar'] ?? '' }}">
                            </div>
                            <div class="col-md-5 form-group mb-2">
                                <label>Feature in English</label>
                                <input type="text" name="features[{{ $featureIndex }}][title_en]" class="form-control" maxlength="180" value="{{ $feature['title_en'] ?? '' }}">
                            </div>
                            <div class="col-md-2 form-group mb-2">
                                <label>أيقونة البند</label>
                                <select name="features[{{ $featureIndex }}][icon_key]" class="form-control">
                                    @foreach($iconOptions as $key => $label)
                                        <option value="{{ $key }}" {{ ($feature['icon_key'] ?? 'star') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-right">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-remove-feature><i class="las la-trash"></i> حذف هذا البند</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-lg mt-3"><i class="las la-plus mr-1"></i> إضافة الخدمة إلى التطبيق</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('click', function (event) {
    const addButton = event.target.closest('[data-add-feature]');
    if (addButton) {
        const list = document.getElementById(addButton.getAttribute('data-target'));
        const prefix = addButton.getAttribute('data-prefix');
        if (list.querySelectorAll('.vip-feature-row').length >= 30) {
            window.alert('يمكن إضافة 30 بنداً كحد أقصى لكل خدمة.');
            return;
        }
        const index = Number(list.dataset.nextIndex || 0);
        list.dataset.nextIndex = String(index + 1);
        const template = document.getElementById('vip-feature-row-template');
        const row = template.content.cloneNode(true);
        row.querySelectorAll('[data-feature-field]').forEach(function (field) {
            field.name = prefix + '[' + index + '][' + field.getAttribute('data-feature-field') + ']';
        });
        const featureNumber = row.querySelector('[data-feature-number]');
        if (featureNumber) featureNumber.textContent = 'بند ' + (index + 1);
        list.appendChild(row);
        return;
    }

    const removeButton = event.target.closest('[data-remove-feature]');
    if (removeButton) {
        const row = removeButton.closest('.vip-feature-row');
        if (row) row.remove();
    }
});
</script>
@endsection
