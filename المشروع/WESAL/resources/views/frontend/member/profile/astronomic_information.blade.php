<style>
    /* التدرج اللامع للعنوان - أسود وأزرق ملكي يتناسب مع الفلك */
    .shiny-astro-title {
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
</style>

<div class="card border-0 shadow-none text-right-force" dir="rtl">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-astro-title">
            <i class="las la-star"></i>
            الأبراج والفلك
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('astrologies.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">ما هو برجكم الفلكي؟</label>
            <p class="text-muted small mb-4">يعتبر التوافق الفلكي من التفاصيل الممتعة والمهمة لدى العديد من الأعضاء. شاركونا برجكم!</p>

            <div class="form-group row">
                <div class="col-md-12 mb-3">
                    <label class="fw-600" for="sun_sign">البرج الشمسي</label>
                    <select class="form-control aiz-selectpicker text-right" name="sun_sign" data-live-search="true" data-size="5">
                        <option value="">اختر برجكم..</option>
                        <option value="الحمل ♈" @if(($member->astrologies->sun_sign ?? '') == 'الحمل ♈') selected @endif>الحمل ♈</option>
                        <option value="الثور ♉" @if(($member->astrologies->sun_sign ?? '') == 'الثور ♉') selected @endif>الثور ♉</option>
                        <option value="الجوزاء ♊" @if(($member->astrologies->sun_sign ?? '') == 'الجوزاء ♊') selected @endif>الجوزاء ♊</option>
                        <option value="السرطان ♋" @if(($member->astrologies->sun_sign ?? '') == 'السرطان ♋') selected @endif>السرطان ♋</option>
                        <option value="الأسد ♌" @if(($member->astrologies->sun_sign ?? '') == 'الأسد ♌') selected @endif>الأسد ♌</option>
                        <option value="العذراء ♍" @if(($member->astrologies->sun_sign ?? '') == 'العذراء ♍') selected @endif>العذراء ♍</option>
                        <option value="الميزان ♎" @if(($member->astrologies->sun_sign ?? '') == 'الميزان ♎') selected @endif>الميزان ♎</option>
                        <option value="العقرب ♏" @if(($member->astrologies->sun_sign ?? '') == 'العقرب ♏') selected @endif>العقرب ♏</option>
                        <option value="القوس ♐" @if(($member->astrologies->sun_sign ?? '') == 'القوس ♐') selected @endif>القوس ♐</option>
                        <option value="الجدي ♑" @if(($member->astrologies->sun_sign ?? '') == 'الجدي ♑') selected @endif>الجدي ♑</option>
                        <option value="الدلو ♒" @if(($member->astrologies->sun_sign ?? '') == 'الدلو ♒') selected @endif>الدلو ♒</option>
                        <option value="الحوت ♓" @if(($member->astrologies->sun_sign ?? '') == 'الحوت ♓') selected @endif>الحوت ♓</option>
                    </select>
                </div>
            </div>

            {{-- حقول مخفية لضمان عدم حدوث أخطاء بقاعدة البيانات --}}
            <input type="hidden" name="moon_sign" value="{{ $member->astrologies->moon_sign ?? '' }}">
            <input type="hidden" name="time_of_birth" value="{{ $member->astrologies->time_of_birth ?? '' }}">
            <input type="hidden" name="city_of_birth" value="{{ $member->astrologies->city_of_birth ?? '' }}">

            <div class="text-left mt-5 border-top pt-3">
                <button type="submit" class="btn btn-primary px-5 fw-800 shadow-primary">
                    حفظ ومتابعة <i class="las la-arrow-left mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>