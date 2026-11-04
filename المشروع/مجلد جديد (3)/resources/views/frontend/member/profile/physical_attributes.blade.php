<style>
    /* التدرج اللامع للعنوان - أسود وأزرق ملكي */
    .shiny-physical-title {
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
    
    /* ضمان المحاذاة لليمين في كل الحقول */
    .text-right-force {
        text-align: right !important;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #e2e5ec;
        height: 45px;
        text-align: right; /* ضمان الكتابة من اليمين داخل الحقل */
    }
</style>

<div class="card border-0 shadow-none text-right-force" dir="rtl">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-physical-title">
            <i class="las la-ruler-vertical"></i>
            السمات والمظهر
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('physical-attribute.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">يرجى تحديد السمات الشخصية الخاصة بكم:</label>
            <p class="text-muted small mb-4">تساعد هذه البيانات في الوصول لنتائج مطابقة أكثر دقة مع تطلعاتكم.</p>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">الطول (سم)</label>
                    <input type="number" name="height" value="{{ $member->physical_attributes->height ?? '' }}" step="any" class="form-control" placeholder="مثال: 175">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">الوزن (كغ)</label>
                    <input type="number" name="weight" value="{{ $member->physical_attributes->weight ?? '' }}" step="any" class="form-control" placeholder="مثال: 70">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">لون العينين</label>
                    <input type="text" name="eye_color" value="{{ $member->physical_attributes->eye_color ?? '' }}" class="form-control" placeholder="مثال: بني، عسلي..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">لون الشعر</label>
                    <input type="text" name="hair_color" value="{{ $member->physical_attributes->hair_color ?? '' }}" class="form-control" placeholder="مثال: أسود، كستنائي..">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">لون البشرة</label>
                    <input type="text" name="complexion" value="{{ $member->physical_attributes->complexion ?? '' }}" class="form-control" placeholder="مثال: بيضاء، حنطية..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">بنية الجسم</label>
                    <select class="form-control aiz-selectpicker text-right" name="body_type" data-minimum-results-for-search="Infinity">
                        <option value="">اختر..</option>
                        <option value="نحيف" @if(($member->physical_attributes->body_type ?? '') == 'نحيف') selected @endif>رشيق / نحيف</option>
                        <option value="متوسط" @if(($member->physical_attributes->body_type ?? '') == 'متوسط') selected @endif>متوسط / رياضي</option>
                        <option value="ممتلئ" @if(($member->physical_attributes->body_type ?? '') == 'ممتلئ') selected @endif>قوام ممتلئ</option>
                        <option value="أفضل عدم الإجابة" @if(($member->physical_attributes->body_type ?? '') == 'أفضل عدم الإجابة') selected @endif>يفضل عدم الذكر</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-12 mb-3">
                    <label class="fw-600">هل يوجد وشم أو رسومات (Tattoo) على الجسم؟</label>
                    <select class="form-control aiz-selectpicker text-right" name="body_art" data-minimum-results-for-search="Infinity">
                        <option value="">اختر الإجابة..</option>
                        <option value="نعم" @if(($member->physical_attributes->body_art ?? '') == 'نعم') selected @endif>نعم، يوجد</option>
                        <option value="لا" @if(($member->physical_attributes->body_art ?? '') == 'لا') selected @endif>لا، لا يوجد</option>
                        <option value="أفضل عدم الإجابة" @if(($member->physical_attributes->body_art ?? '') == 'أفضل عدم الإجابة') selected @endif>يفضل عدم الذكر</option>
                    </select>
                </div>
            </div>

            {{-- حقول إضافية مخفية للحفاظ على هيكلية قاعدة البيانات --}}
            <input type="hidden" name="blood_group" value="{{ $member->physical_attributes->blood_group ?? '' }}">
            <input type="hidden" name="disability" value="{{ $member->physical_attributes->disability ?? '' }}">

            <div class="text-left mt-5 border-top pt-3">
                <button type="submit" class="btn btn-primary px-5 fw-800 shadow-primary">
                    حفظ ومتابعة <i class="las la-arrow-left mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>