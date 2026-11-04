<style>
    /* التدرج اللامع للعنوان - أسود وأزرق ملكي */
    .shiny-residency-title {
        background: linear-gradient(45deg, #000000 30%, #007bff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(0, 123, 255, 0.2));
    }

    /* ضمان المحاذاة لليمين */
    .text-right-force {
        text-align: right !important;
    }

    /* تنسيق القوائم المنسدلة لتكون من اليمين */
    .bootstrap-select .dropdown-toggle .filter-option-inner-inner {
        text-align: right;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #e2e5ec;
        height: 45px;
        text-align: right;
    }
</style>

<div class="card border-0 shadow-none text-right-force" dir="rtl">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-residency-title">
            <i class="las la-globe-americas"></i>
            معلومات الإقامة والسفر
        </h5>
    </div>
    <div class="card-body">
        
        <div class="alert alert-light mb-4 d-flex align-items-center" role="alert" style="border-radius: 12px; border: 1px solid #ffeeba; border-right: 5px solid #ffc107; background-color: #fffdf4;">
            <i class="las la-map-marked-alt la-2x ml-3 text-warning"></i>
            <div>
                <strong style="font-size: 1.05rem; display: block; margin-bottom: 2px; color: #856404;">ملاحظة للمغتربين</strong>
                <span class="text-muted small">يرجى تعبئة هذا القسم في حال كنتم تقيمون خارج بلدكم الأم لتسهيل إيجاد شريك مناسب في نفس بلد إقامتكم.</span>
            </div>
        </div>

        <form action="{{ route('recidencies.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            @php
                $birth_country_id = $member->recidency->birth_country_id ?? "";
                $recidency_country_id = $member->recidency->recidency_country_id ?? "";
                $growup_country_id = $member->recidency->growup_country_id ?? "";
            @endphp

            <label class="chat-label fs-16 fw-700 d-block mb-2">أين تقيمون حالياً وما هو بلدكم الأصلي؟</label>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="birth_country_id">بلد الميلاد</label>
                    <select class="form-control aiz-selectpicker text-right" name="birth_country_id" data-selected="{{ $birth_country_id }}" data-live-search="true">
                        <option value="">اختر بلد الميلاد..</option>
                        @foreach ($countries as $country)
                            <option value="{{$country->id}}">{{$country->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="recidency_country_id">بلد الإقامة الحالي</label>
                    <select class="form-control aiz-selectpicker text-right" name="recidency_country_id" data-selected="{{ $recidency_country_id }}" data-live-search="true">
                        <option value="">اختر بلد الإقامة..</option>
                        @foreach ($countries as $country)
                            <option value="{{$country->id}}" >{{$country->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="growup_country_id">بلد المنشأ (الذي نشأتم فيه)</label>
                    <select class="form-control aiz-selectpicker text-right" name="growup_country_id" data-selected="{{ $growup_country_id }}" data-live-search="true">
                        <option value="">اختر الدولة..</option>
                        @foreach ($countries as $country)
                            <option value="{{$country->id}}">{{$country->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="immigration_status">حالة الإقامة (للمغتربين)</label>
                    <input type="text" name="immigration_status" value="{{ $member->recidency->immigration_status ?? "" }}" placeholder="مثال: إقامة عمل، دراسة، مواطن..." class="form-control">
                </div>
            </div>

            <div class="text-left mt-5 border-top pt-3">
                <button type="submit" class="btn btn-primary px-5 fw-800 shadow-primary">
                    حفظ ومتابعة <i class="las la-arrow-left mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>