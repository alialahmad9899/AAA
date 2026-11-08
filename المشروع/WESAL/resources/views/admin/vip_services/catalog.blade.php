@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3">إدارة كتالوج خدمات VIP</h1>
            <p class="text-muted mb-0">عدّل الأسماء والأسعار والأوصاف، أو أوقف خدمة مؤقتاً دون حذف طلباتها السابقة.</p>
        </div>
        <div class="col-md-4 text-md-right mt-2 mt-md-0">
            <a href="{{ route('admin.vip-services.index') }}" class="btn btn-light">طلبات الخدمات</a>
        </div>
    </div>
</div>

<form action="{{ route('admin.vip-services.catalog.update') }}" method="POST">
    @csrf
    @foreach($services as $index => $service)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">{{ $service['title_ar'] ?? $service['title'] ?? $service['service_type'] }}</h5>
                <span class="badge {{ !empty($service['active']) ? 'badge-success' : 'badge-secondary' }}">
                    {{ !empty($service['active']) ? 'مفعّلة' : 'متوقفة' }}
                </span>
            </div>
            <div class="card-body">
                <input type="hidden" name="services[{{ $index }}][service_type]" value="{{ $service['service_type'] }}">
                <input type="hidden" name="services[{{ $index }}][active]" value="0">

                <div class="row">
                    <div class="col-md-8 form-group">
                        <label>اسم الخدمة بالعربية</label>
                        <input type="text" name="services[{{ $index }}][title_ar]" class="form-control" maxlength="180" required
                            value="{{ old('services.'.$index.'.title_ar', $service['title_ar'] ?? $service['title']) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>السعر بالدولار الأمريكي</label>
                        <div class="input-group">
                            <input type="number" name="services[{{ $index }}][price]" class="form-control" min="0.01" max="100000" step="0.01" required
                                value="{{ old('services.'.$index.'.price', $service['price']) }}">
                            <div class="input-group-append"><span class="input-group-text">USD</span></div>
                        </div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>اسم الخدمة بالإنجليزية</label>
                        <input type="text" name="services[{{ $index }}][title_en]" class="form-control" maxlength="180"
                            value="{{ old('services.'.$index.'.title_en', $service['title_en'] ?? '') }}">
                    </div>
                    <div class="col-md-6 form-group d-flex align-items-end">
                        <label class="mb-2">
                            <input type="checkbox" name="services[{{ $index }}][active]" value="1" {{ !empty($service['active']) ? 'checked' : '' }}>
                            تفعيل الخدمة وإظهارها في التطبيق
                        </label>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>الوصف بالعربية</label>
                        <textarea name="services[{{ $index }}][promo_ar]" class="form-control" rows="4" maxlength="1500" required>{{ old('services.'.$index.'.promo_ar', $service['promo_ar'] ?? $service['description']) }}</textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>الوصف بالإنجليزية</label>
                        <textarea name="services[{{ $index }}][promo_en]" class="form-control" rows="4" maxlength="1500">{{ old('services.'.$index.'.promo_en', $service['promo_en'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="text-right mb-4">
        <button type="submit" class="btn btn-primary btn-lg">حفظ كتالوج خدمات VIP</button>
    </div>
</form>
@endsection
