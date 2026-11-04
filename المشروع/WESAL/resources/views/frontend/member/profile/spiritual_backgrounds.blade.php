<style>
    /* التدرج اللامع للعنوان - أسود وأزرق ملكي */
    .shiny-spiritual-title {
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
    
    /* تنسيق القوائم المنسدلة */
    .bootstrap-select .dropdown-toggle .filter-option-inner-inner {
        text-align: right;
    }
</style>

<div class="card border-0 shadow-none text-right-force" dir="rtl">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-spiritual-title">
            <i class="las la-dove"></i>
            الخلفية الدينية والاجتماعية
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('spiritual_backgrounds.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">ما هي خلفيتكم الدينية وقيمكم العائلية؟</label>
            <p class="text-muted small mb-4">يعتبر التوافق الروحي والفكري من أهم أسس الارتباط الناجح، يرجى تحديد ما يناسبكم.</p>

            <div class="form-group row">
                {{-- حقل الدين --}}
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="member_religion_id">الديانة</label>
                    <select class="form-control aiz-selectpicker text-right" name="member_religion_id" id="member_religion_id" data-selected="{{ $member->spiritual_backgrounds->religion_id ?? $member_religion_id }}" data-live-search="true">
                        <option value="">اختر الديانة..</option>
                        @foreach ($religions as $religion)
                            <option value="{{$religion->id}}"> {{ $religion->name }} </option>
                        @endforeach
                    </select>
                </div>

                {{-- حقل القيم العائلية --}}
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="family_value_id">القيم العائلية</label>
                    <select class="form-control aiz-selectpicker text-right" name="family_value_id" data-selected="{{ $member->spiritual_backgrounds->family_value_id ?? '' }}" data-live-search="true">
                        <option value="">اختر القيم العائلية الأقرب لكم..</option>
                        @foreach ($family_values as $family_value)
                            <option value="{{$family_value->id}}"> {{ $family_value->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- الحقول المخفية: نرسل فيها القيم المخزنة حالياً لضمان عدم مسحها أو رفض السيرفر لها --}}
            <input type="hidden" name="member_caste_id" id="member_caste_id" value="{{ $member->spiritual_backgrounds->caste_id ?? $member_caste_id }}">
            <input type="hidden" name="member_sub_caste_id" id="member_sub_caste_id" value="{{ $member->spiritual_backgrounds->sub_caste_id ?? $member_sub_caste_id }}">
            <input type="hidden" name="ethnicity" value="{{ $member->spiritual_backgrounds->ethnicity ?? '' }}">
            <input type="hidden" name="personal_value" value="{{ $member->spiritual_backgrounds->personal_value ?? '' }}">
            <input type="hidden" name="community_value" value="{{ $member->spiritual_backgrounds->community_value ?? '' }}">

            <div class="text-left mt-5 border-top pt-3">
                <button type="submit" class="btn btn-primary px-5 fw-800 shadow-primary">
                    حفظ ومتابعة <i class="las la-arrow-left mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>