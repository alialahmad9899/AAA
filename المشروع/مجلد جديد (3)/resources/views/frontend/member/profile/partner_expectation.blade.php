<style>
    /* التدرج اللامع الملكي للعنوان */
    .shiny-partner-title {
        background: linear-gradient(45deg, #000000 30%, #d4af37 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(212, 175, 55, 0.3));
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
        <h5 class="shiny-partner-title">
            <i class="las la-search-heart"></i>
            توقعات ومواصفات الشريك
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('partner_expectations.update', $member->id) }}" method="POST">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">ما هي المواصفات التي تبحثون عنها في شريك المستقبل؟</label>
            <p class="text-muted small mb-4">هذا القسم هو الأهم! خوارزميات المنصة ستعتمد على هذه الخيارات لترشيح الأنسب لكم.</p>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="general">مواصفات عامة تتمناها</label>
                    <input type="text" name="general" value="{{ $member->partner_expectations->general ?? '' }}" class="form-control" placeholder="مثال: حنون، مثقف، طموح، يقدر الحياة الزوجية...">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="residence_country_id">بلد الإقامة المفضل للشريك</label>
                    <select class="form-control aiz-selectpicker text-right" name="residence_country_id" data-selected="{{ $member->partner_expectations->residence_country_id ?? '' }}" data-live-search="true">
                        <option value="">لا يهم / أي دولة</option>
                        @foreach ($countries as $country)
                            <option value="{{$country->id}}">{{$country->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="partner_height">الحد الأدنى للطول (سم)</label>
                    {{-- 🛑 السحر هنا: تحويل المتر إلى سنتيمتر عند العرض --}}
                    @php
                        $p_height = $member->partner_expectations->height ?? '';
                        if(is_numeric($p_height) && $p_height > 0 && $p_height < 10) {
                            $p_height = $p_height * 100;
                        }
                    @endphp
                    <input type="number" name="partner_height" value="{{ $p_height }}" step="any" placeholder="مثال: 160" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="partner_weight">الحد الأقصى للوزن (كغ)</label>
                    <input type="number" name="partner_weight" value="{{ $member->partner_expectations->weight ?? '' }}" step="any" class="form-control" placeholder="مثال: 80">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="partner_marital_status">الحالة الاجتماعية المفضلة</label>
                    <select class="form-control aiz-selectpicker text-right" name="partner_marital_status" data-selected="{{ $member->partner_expectations->marital_status_id ?? '' }}" data-live-search="true">
                        <option value="">لا يهم</option>
                        @foreach ($marital_statuses as $marital_status)
                        <option value="{{$marital_status->id}}">{{$marital_status->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="partner_children_acceptable">هل تقبلون بوجود أطفال لدى الشريك؟</label>
                    @php $children_acceptable = $member->partner_expectations->children_acceptable ?? ""; @endphp
                    <select class="form-control aiz-selectpicker text-right" name="partner_children_acceptable" data-minimum-results-for-search="Infinity">
                        <option value="dose_not_matter" @if($children_acceptable == 'dose_not_matter') selected @endif >لا يهم</option>
                        <option value="yes" @if($children_acceptable == 'yes') selected @endif >نعم، أتقبل ذلك</option>
                        <option value="no" @if($children_acceptable == 'no') selected @endif >لا، أفضل شريكاً بدون أطفال</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="partner_religion_id">الديانة</label>
                    <select class="form-control aiz-selectpicker text-right" name="partner_religion_id" id="partner_religion_id" data-selected="{{ $member->partner_expectations->religion_id ?? '' }}" data-live-search="true">
                        <option value="">لا يهم</option>
                        @foreach ($religions as $religion)
                            <option value="{{$religion->id}}"> {{ $religion->name }} </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="language_id">اللغة الأساسية</label>
                    <select class="form-control aiz-selectpicker text-right" name="language_id" data-selected="{{ $member->partner_expectations->language_id ?? '' }}" data-live-search="true">
                        <option value="">لا يهم</option>
                        @foreach ($languages as $language)
                            <option value="{{$language->id}}"> {{ $language->name }} </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="pertner_education">الحد الأدنى للمستوى التعليمي</label>
                    <select name="pertner_education" class="form-control aiz-selectpicker text-right">
                        <option value="">لا يهم</option>
                        @php $edu = $member->partner_expectations->education ?? ''; @endphp
                        <option value="إعدادية" @if($edu == 'إعدادية') selected @endif>إعدادية</option>
                        <option value="ثانوية" @if($edu == 'ثانوية') selected @endif>ثانوية</option>
                        <option value="دبلوم" @if($edu == 'دبلوم') selected @endif>دبلوم</option>
                        <option value="بكالوريوس" @if($edu == 'بكالوريوس') selected @endif>بكالوريوس</option>
                        <option value="ماجستير" @if($edu == 'ماجستير') selected @endif>ماجستير</option>
                        <option value="دكتوراه" @if($edu == 'دكتوراه') selected @endif>دكتوراه</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="partner_profession">المهنة المفضلة</label>
                    <input type="text" name="partner_profession" value="{{ $member->partner_expectations->profession ?? '' }}" class="form-control" placeholder="مثال: طبيب، مهندس، مدرس، لا يهم...">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-4 mb-3">
                    <label class="fw-600" for="smoking_acceptable">هل تقبلون شريكاً مدخناً؟</label>
                    @php $partner_smoking_acceptable = $member->partner_expectations->smoking_acceptable ?? ""; @endphp
                    <select class="form-control aiz-selectpicker text-right" name="smoking_acceptable" data-minimum-results-for-search="Infinity">
                        <option value="dose_not_matter" @if($partner_smoking_acceptable == 'dose_not_matter') selected @endif >لا يهم</option>
                        <option value="no" @if($partner_smoking_acceptable == 'no') selected @endif >لا (أشترط ألا يكون مدخناً)</option>
                        <option value="yes" @if($partner_smoking_acceptable == 'yes') selected @endif >نعم (لا مانع من التدخين)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-600" for="pertner_complexion">لون البشرة المفضل</label>
                    <input type="text" name="pertner_complexion" value="{{ $member->partner_expectations->complexion ?? '' }}" class="form-control" placeholder="مثال: بيضاء، سمراء، لا يهم...">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-600" for="drinking_acceptable">رأيكم في تناول الشريك للكحول؟</label>
                    @php $partner_drinking_acceptable = $member->partner_expectations->drinking_acceptable ?? ""; @endphp
                    <select class="form-control aiz-selectpicker text-right" name="drinking_acceptable" data-minimum-results-for-search="Infinity">
                        <option value="no" @if($partner_drinking_acceptable == 'no') selected @endif >مرفوض تماماً</option>
                        <option value="dose_not_matter" @if($partner_drinking_acceptable == 'dose_not_matter') selected @endif >لا يهم</option>
                        <option value="yes" @if($partner_drinking_acceptable == 'yes') selected @endif >مقبول</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="partner_caste_id" value="">
            <input type="hidden" name="partner_sub_caste_id" value="">
            <input type="hidden" name="partner_body_type" value="">
            <input type="hidden" name="partner_personal_value" value="">
            <input type="hidden" name="partner_manglik" value="">
            <input type="hidden" name="partner_diet" value="">
            <input type="hidden" name="family_value_id" value="{{ $member->partner_expectations->family_value_id ?? '' }}">

            <div class="text-left mt-5 border-top pt-3">
                <button type="submit" class="btn btn-success px-5 fw-800 shadow-success submit-step-btn" style="border-radius: 10px;">
                    حفظ وإنهاء <i class="las la-check-double mr-1"></i>
                </button>
            </div>

        </form>
    </div>
</div>