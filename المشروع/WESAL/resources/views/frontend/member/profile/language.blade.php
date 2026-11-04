<style>
    /* التدرج اللامع للعنوان - أسود وبرتقالي (يعبر عن التواصل والثقافة) */
    .shiny-language-title {
        background: linear-gradient(45deg, #000000 30%, #e67e22 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(230, 126, 34, 0.2));
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
        text-align: right;
    }
</style>

<div class="card border-0 shadow-none text-right-force" dir="rtl">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-language-title">
            <i class="las la-language"></i>
            اللغات والثقافة
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('member.language_info_update', $member->id) }}" method="POST" class="wizard-ajax-form">
            @csrf
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">ما هي اللغات التي تتقنونها؟</label>
            <p class="text-muted small mb-4">التواصل هو أساس أي علاقة ناجحة. يمكنكم اختيار أكثر من لغة.</p>

            <div class="form-group row mb-3">
                
                {{-- تم إخفاء اللغة الأم بذكاء (بدل الحذف) لتفادي أي خطأ من السيرفر --}}
                <div class="col-md-6 d-none">
                    <label for="mothere_tongue">{{translate('Mother Tongue')}}</label>
                    <select class="form-control aiz-selectpicker" name="mothere_tongue" data-selected="{{ $member->member->mothere_tongue }}" data-live-search="true">
                        <option value="">{{translate('Select One')}}</option>
                        @foreach ($languages as $language)
                            <option value="{{$language->id}}"> {{ $language->name }} </option>
                        @endforeach
                    </select>
                </div>

                {{-- سؤال اللغات اللي بيتقنها العضو (أخذ المساحة الكاملة) --}}
                <div class="col-md-12 mb-3">
                    <label for="known_languages" class="fw-600">اللغات التي تتحدثون بها بطلاقة</label>
                    @php $known_languages = !empty($member->member->known_languages) ? json_decode($member->member->known_languages) : [] ; @endphp
                    <select class="form-control aiz-selectpicker text-right" name="known_languages[]" data-live-search="true" multiple title="اضغط لاختيار اللغات..." data-selected-text-format="count > 2">
                        @foreach ($languages as $language)
                            <option value="{{$language->id}}" @if(in_array($language->id, $known_languages)) selected @endif >{{ $language->name }} </option>
                        @endforeach
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