<style>
    /* التدرج اللامع للعنوان - أسود وأزرق منعش يتناسب مع نمط الحياة */
    .shiny-lifestyle-title {
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

    /* محاذاة إجبارية لليمين */
    .text-right-force {
        text-align: right !important;
    }
    
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
        <h5 class="shiny-lifestyle-title">
            <i class="las la-coffee"></i>
            نمط الحياة اليومي
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('lifestyles.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">كيف تقضون تفاصيل حياتكم اليومية؟</label>
            <p class="text-muted small mb-4">التشابه في نمط الحياة اليومي والعادات يسهل الكثير من التفاهم بين الشريكين.</p>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="diet">هل تتبعون نظاماً غذائياً معيناً؟</label>
                    @php $user_diet = !empty($member->lifestyles->diet) ? $member->lifestyles->diet : ""; @endphp
                    <select class="form-control aiz-selectpicker text-right" name="diet" data-minimum-results-for-search="Infinity">
                        <option value="">اختر الإجابة..</option>
                        <option value="yes" @if($user_diet == 'yes') selected @endif >نعم، أتبع نظام غذائي (دايت/نباتي)</option>
                        <option value="no" @if($user_diet == 'no') selected @endif >لا، آكل كل شيء بشكل طبيعي</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="living_with">مع من تعيشون حالياً؟</label>
                    <input type="text" name="living_with" value="{{ $member->lifestyles->living_with ?? "" }}" placeholder="مثال: مع الوالدين، أعيش بمفردي.." class="form-control">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="smoke">التدخين</label>
                    @php $user_smoke = !empty($member->lifestyles->smoke) ? $member->lifestyles->smoke : ""; @endphp
                    <select class="form-control aiz-selectpicker text-right" name="smoke" data-minimum-results-for-search="Infinity">
                        <option value="">اختر الإجابة..</option>
                        <option value="yes" @if($user_smoke == 'yes') selected @endif >نعم، أدخن</option>
                        <option value="no" @if($user_smoke == 'no') selected @endif >لا أدخن أبداً</option>
                        <option value="أحياناً" @if($user_smoke == 'أحياناً') selected @endif >أحياناً (أرجيلة/فيب)</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="drink">هل تشربون الكحول؟</label>
                    @php $user_drink = !empty($member->lifestyles->drink) ? $member->lifestyles->drink : ""; @endphp
                    <select class="form-control aiz-selectpicker text-right" name="drink" data-minimum-results-for-search="Infinity">
                        <option value="">اختر الإجابة..</option>
                        <option value="no" @if($user_drink == 'no') selected @endif >لا، أبداً</option>
                        <option value="yes" @if($user_drink == 'yes') selected @endif >نعم</option>
                        <option value="أفضل عدم الإجابة" @if($user_drink == 'أفضل عدم الإجابة') selected @endif >أفضل عدم الذكر</option>
                    </select>
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