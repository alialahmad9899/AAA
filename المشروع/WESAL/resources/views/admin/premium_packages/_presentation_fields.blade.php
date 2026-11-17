@php
    $presentation = $presentation ?? [];
    $featureRows = old('features', $featureRows ?? []);
    $badgeStyleValue = old('badge_style', $presentation['badge_style'] ?? 'teal');
    $badgeIconValue = old('badge_icon_key', $presentation['badge_icon_key'] ?? 'workspace_premium');
@endphp

<div class="border rounded p-3 mb-3 bg-light">
    <h6 class="mb-3"><i class="las la-palette"></i> مظهر الباقة في تطبيق الموبايل</h6>
    <div class="form-group row">
        <label class="col-md-3 col-form-label" for="badge_text_ar">شارة الباقة بالعربية</label>
        <div class="col-md-9">
            <input type="text" name="badge_text_ar" id="badge_text_ar" class="form-control" maxlength="80"
                   value="{{ old('badge_text_ar', $presentation['badge_text_ar'] ?? '') }}" placeholder="مثال: الباقة الذهبية">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label" for="badge_text_en">شارة الباقة بالإنجليزية</label>
        <div class="col-md-9">
            <input type="text" name="badge_text_en" id="badge_text_en" class="form-control" maxlength="80"
                   value="{{ old('badge_text_en', $presentation['badge_text_en'] ?? '') }}" placeholder="Example: Gold Plan">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label" for="badge_style">لون الشارة</label>
        <div class="col-md-9">
            <select name="badge_style" id="badge_style" class="form-control" required>
                @foreach($badgeStyles as $key => $label)
                    <option value="{{ $key }}" {{ $badgeStyleValue === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label" for="badge_icon_key">أيقونة الشارة</label>
        <div class="col-md-9">
            <select name="badge_icon_key" id="badge_icon_key" class="form-control" required>
                @foreach($iconOptions as $key => $label)
                    <option value="{{ $key }}" {{ $badgeIconValue === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">إبراز الباقة</label>
        <div class="col-md-9 mt-2">
            <input type="hidden" name="is_featured" value="0">
            <label class="aiz-switch aiz-switch-success mb-0">
                <input type="checkbox" name="is_featured" value="1"
                    {{ old('is_featured', !empty($presentation['is_featured']) ? '1' : '0') == '1' ? 'checked' : '' }}>
                <span></span>
            </label>
            <small class="text-muted d-block">تمييز بصري اختياري في التطبيق، ولا يغيّر السعر أو الاستحقاقات.</small>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label" for="duration_label_ar">وصف المدة بالعربية (اختياري)</label>
        <div class="col-md-9">
            <input type="text" name="duration_label_ar" id="duration_label_ar" class="form-control" maxlength="80"
                   value="{{ old('duration_label_ar', $presentation['duration_label_ar'] ?? '') }}" placeholder="مثال: اشتراك شهري">
            <small class="text-muted">يظهر كتسمية فقط؛ مدة الصلاحية الفعلية تبقى عدد الأيام المحدد في حقل الباقة.</small>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label" for="duration_label_en">وصف المدة بالإنجليزية (اختياري)</label>
        <div class="col-md-9">
            <input type="text" name="duration_label_en" id="duration_label_en" class="form-control" maxlength="80"
                   value="{{ old('duration_label_en', $presentation['duration_label_en'] ?? '') }}" placeholder="Example: Monthly Plan">
        </div>
    </div>
</div>

<div class="border rounded p-3 mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <div>
            <h6 class="mb-1"><i class="las la-list-ul"></i> مزايا الباقة التي تظهر في التطبيق</h6>
            <small class="text-muted">أضف حتى 30 ميزة. لكل ميزة عنوان عربي وإنجليزي وأيقونة مستقلة.</small>
        </div>
        <button type="button" class="btn btn-soft-primary btn-sm" id="add-package-feature">
            <i class="las la-plus"></i> إضافة ميزة
        </button>
    </div>
    <div id="package-feature-list" data-next-index="{{ count($featureRows) }}">
        @foreach($featureRows as $index => $feature)
            <div class="border rounded p-3 mb-2 package-feature-row">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>الميزة <span class="package-feature-number">{{ $index + 1 }}</span></strong>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-package-feature">
                        <i class="las la-trash"></i> حذف
                    </button>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>العنوان بالعربية</label>
                        <input type="text" name="features[{{ $index }}][title_ar]" class="form-control" maxlength="180"
                               value="{{ $feature['title_ar'] ?? '' }}">
                    </div>
                    <div class="form-group col-md-5">
                        <label>Title in English</label>
                        <input type="text" name="features[{{ $index }}][title_en]" class="form-control" maxlength="180"
                               value="{{ $feature['title_en'] ?? '' }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>الأيقونة</label>
                        <select name="features[{{ $index }}][icon_key]" class="form-control">
                            @foreach($iconOptions as $key => $label)
                                <option value="{{ $key }}" {{ ($feature['icon_key'] ?? 'check') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div id="package-feature-empty" class="text-muted small {{ count($featureRows) ? 'd-none' : '' }}">
        لم تتم إضافة مزايا مخصصة بعد. يمكنك ترك القائمة فارغة، ولن يعرض التطبيق مزايا تسويقية ثابتة غير مؤكدة.
    </div>
</div>

<template id="package-feature-template">
    <div class="border rounded p-3 mb-2 package-feature-row">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>الميزة <span class="package-feature-number"></span></strong>
            <button type="button" class="btn btn-outline-danger btn-sm remove-package-feature">
                <i class="las la-trash"></i> حذف
            </button>
        </div>
        <div class="form-row">
            <div class="form-group col-md-5">
                <label>العنوان بالعربية</label>
                <input type="text" name="features[__INDEX__][title_ar]" class="form-control" maxlength="180">
            </div>
            <div class="form-group col-md-5">
                <label>Title in English</label>
                <input type="text" name="features[__INDEX__][title_en]" class="form-control" maxlength="180">
            </div>
            <div class="form-group col-md-2">
                <label>الأيقونة</label>
                <select name="features[__INDEX__][icon_key]" class="form-control">
                    @foreach($iconOptions as $key => $label)
                        <option value="{{ $key }}" {{ $key === 'check' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    const list = document.getElementById('package-feature-list');
    const addButton = document.getElementById('add-package-feature');
    const template = document.getElementById('package-feature-template');
    const emptyState = document.getElementById('package-feature-empty');
    if (!list || !addButton || !template) return;

    function renumber() {
        const rows = list.querySelectorAll('.package-feature-row');
        rows.forEach((row, index) => {
            const number = row.querySelector('.package-feature-number');
            if (number) number.textContent = String(index + 1);
        });
        if (emptyState) emptyState.classList.toggle('d-none', rows.length > 0);
        addButton.disabled = rows.length >= 30;
    }

    addButton.addEventListener('click', function () {
        const rows = list.querySelectorAll('.package-feature-row');
        if (rows.length >= 30) return;
        const index = Number(list.dataset.nextIndex || 0);
        list.dataset.nextIndex = String(index + 1);
        const html = template.innerHTML.replace(/__INDEX__/g, String(index));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;
        if (row) list.appendChild(row);
        renumber();
    });

    list.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-package-feature');
        if (!button) return;
        const row = button.closest('.package-feature-row');
        if (row) row.remove();
        renumber();
    });

    renumber();
})();
</script>
