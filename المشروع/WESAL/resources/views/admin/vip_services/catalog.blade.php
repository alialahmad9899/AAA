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
@endphp

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3">إدارة كتالوج خدمات VIP</h1>
            <p class="text-muted mb-0">تحكم كامل بالخدمات التي تظهر في تطبيق وصال: التفاصيل والمزايا والأيقونة والسعر والترتيب.</p>
        </div>
        <div class="col-md-4 text-md-right mt-2 mt-md-0">
            <a href="{{ route('admin.vip-services.index') }}" class="btn btn-light">طلبات الخدمات</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-2"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center"><div class="bg-soft-success rounded-circle p-3 mr-3"><i class="las la-check-circle text-success la-2x"></i></div><div><div class="text-muted small">الخدمات الظاهرة في التطبيق</div><div class="h3 mb-0">{{ $activeCount }}</div></div></div></div></div>
    <div class="col-md-4 mb-2"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center"><div class="bg-soft-secondary rounded-circle p-3 mr-3"><i class="las la-layer-group text-secondary la-2x"></i></div><div><div class="text-muted small">إجمالي الخدمات</div><div class="h3 mb-0">{{ count($services) }}</div></div></div></div></div>
    <div class="col-md-4 mb-2"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center"><div class="bg-soft-warning rounded-circle p-3 mr-3"><i class="las la-archive text-warning la-2x"></i></div><div><div class="text-muted small">الخدمات المؤرشفة</div><div class="h3 mb-0">{{ $archivedCount }}</div></div></div></div></div>
</div>

<div class="alert alert-info">
    <i class="las la-info-circle mr-1"></i>
    تفاصيل ومزايا كل خدمة تظهر في التطبيق كما تُكتب هنا. الخدمة التي لديها طلبات سابقة تُؤرشف عند إلغائها حتى لا تضيع بيانات الطلبات.
</div>

@if($errors->any())
<div class="alert alert-danger"><strong>يرجى مراجعة البيانات:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form action="{{ route('admin.vip-services.catalog.update') }}" method="POST">
    @csrf
    @foreach($services as $index => $service)
        @php
            $includedAr = implode("\n", $service['included_ar'] ?? []);
            $includedEn = implode("\n", $service['included_en'] ?? []);
            $serviceArchived = !empty($service['archived']);
            $serviceActive = !empty($service['active']) && !$serviceArchived;
        @endphp
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center">
                    <span class="rounded-circle bg-soft-warning text-warning p-3 mr-3"><i class="las {{ $iconClasses[$service['icon_key']] ?? 'la-star' }} la-2x"></i></span>
                    <div><h5 class="mb-1">{{ $service['title_ar'] ?: $service['service_type'] }}</h5><small class="text-muted">{{ $service['service_type'] }} · #{{ $service['id'] ?? '-' }}</small></div>
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
                        <div class="mt-2"><label class="mb-0"><input type="checkbox" name="services[{{ $index }}][active]" value="1" {{ $serviceActive ? 'checked' : '' }} {{ $serviceArchived ? 'disabled' : '' }}> إظهار الخدمة وقبول طلبات جديدة</label></div>
                    </div>
                    <div class="col-md-8 form-group">
                        <label>التفاصيل المختصرة بالإنجليزية</label>
                        <textarea name="services[{{ $index }}][promo_en]" class="form-control" rows="3" maxlength="1500">{{ old('services.'.$index.'.promo_en', $service['promo_en']) }}</textarea>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>الأيقونة الرئيسية</label>
                        <select name="services[{{ $index }}][icon_key]" class="form-control" required>
                            @foreach($iconOptions as $key => $label)
                                <option value="{{ $key }}" {{ $service['icon_key'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label class="mt-2">ترتيب الظهور</label>
                        <input type="number" name="services[{{ $index }}][sort_order]" class="form-control" min="0" max="9999" required value="{{ old('services.'.$index.'.sort_order', $service['sort_order']) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>المزايا والتفاصيل الكاملة بالعربية</label>
                        <textarea name="services[{{ $index }}][included_ar_text]" class="form-control" rows="6" maxlength="5000" placeholder="اكتب كل ميزة في سطر مستقل">{{ old('services.'.$index.'.included_ar_text', $includedAr) }}</textarea>
                        <small class="text-muted">كل سطر يظهر كبند مستقل في نافذة عرض المزايا داخل التطبيق.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>المزايا والتفاصيل الكاملة بالإنجليزية</label>
                        <textarea name="services[{{ $index }}][included_en_text]" class="form-control" rows="6" maxlength="5000" placeholder="One feature per line">{{ old('services.'.$index.'.included_en_text', $includedEn) }}</textarea>
                    </div>
                </div>
                <div class="border-top pt-3">
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
                    <select name="icon_key" class="form-control" required>
                        @foreach($iconOptions as $key => $label)<option value="{{ $key }}" {{ old('icon_key', 'star') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                    </select>
                    <label class="mt-2">ترتيب الظهور</label><input type="number" name="sort_order" class="form-control" min="0" max="9999" value="{{ old('sort_order', 40) }}" required>
                    <div class="mt-2"><input type="hidden" name="active" value="0"><label><input type="checkbox" name="active" value="1" checked> تفعيل فور الإضافة</label></div>
                </div>
                <div class="col-md-6 form-group"><label>مزايا الخدمة بالعربية</label><textarea name="included_ar_text" class="form-control" rows="6" maxlength="5000" placeholder="ميزة في كل سطر">{{ old('included_ar_text') }}</textarea></div>
                <div class="col-md-6 form-group"><label>مزايا الخدمة بالإنجليزية</label><textarea name="included_en_text" class="form-control" rows="6" maxlength="5000" placeholder="One feature per line">{{ old('included_en_text') }}</textarea></div>
            </div>
            <button type="submit" class="btn btn-success btn-lg"><i class="las la-plus mr-1"></i> إضافة الخدمة إلى التطبيق</button>
        </form>
    </div>
</div>
@endsection
