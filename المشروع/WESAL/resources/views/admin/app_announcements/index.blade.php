@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-1" style="font-family:'Cairo',sans-serif;font-weight:700;color:#0f172a;">
                <i class="las la-bullhorn text-primary mr-1"></i> نافذة الملاحظات وتحديثات التطبيق
            </h1>
            <p class="text-muted mb-0" style="font-family:'Cairo',sans-serif;">
                تحكم بالرسالة والجمهور والزر الذي يظهر لأعضاء تطبيق وصال عند دخولهم.
            </p>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>يرجى مراجعة الحقول التالية:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.app-announcement.update') }}" method="POST">
    @csrf
    <div class="card shadow-sm border-0 rounded-lg mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0 h6 font-weight-bold" style="font-family:'Cairo',sans-serif;">إعداد النافذة</h5>
            <label class="mb-0 d-flex align-items-center">
                <input type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $announcement->is_active ?? false) ? 'checked' : '' }}>
                <span class="mr-2 font-weight-bold">تفعيل ظهور النافذة</span>
            </label>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="font-weight-bold">نوع النافذة</label>
                <select name="announcement_type" class="form-control" required>
                    <option value="note" {{ old('announcement_type', $announcement->announcement_type ?? 'note') === 'note' ? 'selected' : '' }}>ملاحظة أو تنبيه</option>
                    <option value="app_update" {{ old('announcement_type', $announcement->announcement_type ?? 'note') === 'app_update' ? 'selected' : '' }}>تحديث التطبيق</option>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">العنوان بالعربية</label>
                        <input type="text" name="title_ar" class="form-control" maxlength="150" required
                            value="{{ old('title_ar', $announcement->title_ar ?? '') }}" placeholder="مثال: تحديث جديد في وصال">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">العنوان بالإنجليزية (اختياري)</label>
                        <input type="text" name="title_en" class="form-control" maxlength="150"
                            value="{{ old('title_en', $announcement->title_en ?? '') }}" placeholder="Optional English title">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">نص الرسالة بالعربية</label>
                <textarea name="message_ar" class="form-control" rows="5" maxlength="10000" required
                    placeholder="اكتب الملاحظة أو تفاصيل التحديث التي ستظهر للعضو...">{{ old('message_ar', $announcement->message_ar ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label class="font-weight-bold">نص الرسالة بالإنجليزية (اختياري)</label>
                <textarea name="message_en" class="form-control" rows="4" maxlength="10000"
                    placeholder="Optional English message">{{ old('message_en', $announcement->message_en ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0 h6 font-weight-bold" style="font-family:'Cairo',sans-serif;">تحديد الأعضاء المستهدفين</h5>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="font-weight-bold">من الذي يرى النافذة؟</label>
                <select name="target_type" id="announcement-target" class="form-control" required>
                    <option value="all" {{ old('target_type', $announcement->target_type ?? 'all') === 'all' ? 'selected' : '' }}>جميع الأعضاء</option>
                    <option value="male" {{ old('target_type', $announcement->target_type ?? 'all') === 'male' ? 'selected' : '' }}>الذكور فقط</option>
                    <option value="female" {{ old('target_type', $announcement->target_type ?? 'all') === 'female' ? 'selected' : '' }}>الإناث فقط</option>
                    <option value="age" {{ old('target_type', $announcement->target_type ?? 'all') === 'age' ? 'selected' : '' }}>حسب الفئة العمرية</option>
                </select>
            </div>
            <div class="row" id="announcement-age-range">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">العمر من</label>
                        <input type="number" name="age_min" class="form-control" min="18" max="100"
                            value="{{ old('age_min', $announcement->age_min ?? 18) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">العمر إلى</label>
                        <input type="number" name="age_max" class="form-control" min="18" max="100"
                            value="{{ old('age_max', $announcement->age_max ?? 100) }}">
                    </div>
                </div>
            </div>
            <small class="text-muted">عند اختيار العمر، تُحسب الفئة من تاريخ الميلاد المسجل في ملف العضو، والحدّان مشمولان.</small>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0 h6 font-weight-bold" style="font-family:'Cairo',sans-serif;">الزر الاختياري والرابط</h5>
            <label class="mb-0 d-flex align-items-center">
                <input type="checkbox" name="button_enabled" id="announcement-button-enabled" value="1"
                    {{ old('button_enabled', $announcement->button_enabled ?? false) ? 'checked' : '' }}>
                <span class="mr-2 font-weight-bold">إظهار زر داخل النافذة</span>
            </label>
        </div>
        <div class="card-body" id="announcement-button-fields">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">نص الزر بالعربية</label>
                        <input type="text" name="button_text_ar" class="form-control" maxlength="80"
                            value="{{ old('button_text_ar', $announcement->button_text_ar ?? '') }}" placeholder="مثال: تحديث التطبيق">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">نص الزر بالإنجليزية</label>
                        <input type="text" name="button_text_en" class="form-control" maxlength="80"
                            value="{{ old('button_text_en', $announcement->button_text_en ?? '') }}" placeholder="Optional button label">
                    </div>
                </div>
            </div>
            <div class="form-group mb-0">
                <label class="font-weight-bold">رابط الزر</label>
                <input type="url" name="button_url" class="form-control" maxlength="2048"
                    value="{{ old('button_url', $announcement->button_url ?? '') }}" placeholder="https://play.google.com/store/apps/details?id=...">
                <small class="text-muted">يُقبل رابط ويب يبدأ بـ http أو https فقط.</small>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-4">
        <button type="submit" class="btn btn-primary px-5">
            <i class="las la-save mr-1"></i> حفظ إعدادات النافذة
        </button>
    </div>
</form>
@endsection

@section('script')
<script>
(function () {
    const target = document.getElementById('announcement-target');
    const ageFields = document.getElementById('announcement-age-range');
    const buttonToggle = document.getElementById('announcement-button-enabled');
    const buttonFields = document.getElementById('announcement-button-fields');

    function refreshVisibility() {
        if (target && ageFields) {
            ageFields.style.display = target.value === 'age' ? '' : 'none';
        }
        if (buttonToggle && buttonFields) {
            buttonFields.style.display = buttonToggle.checked ? '' : 'none';
        }
    }

    if (target) target.addEventListener('change', refreshVisibility);
    if (buttonToggle) buttonToggle.addEventListener('change', refreshVisibility);
    refreshVisibility();
})();
</script>
@endsection
