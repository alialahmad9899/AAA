<style>
    /* التدرج اللامع للعنوان - أسود وأزرق (Location Theme) */
    .shiny-address-title {
        background: linear-gradient(45deg, #000000 30%, #1877f2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(24, 119, 242, 0.2));
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
        <h5 class="shiny-address-title">
            <i class="las la-map-marker-alt"></i>
            مكان الإقامة الحالي
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('address.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            <input type="hidden" name="address_type" value="present">
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">أين تقيمون حالياً؟</label>
            <p class="text-muted small mb-4">يساعدنا ذلك في اقتراح شركاء محتملين قريبين من منطقتكم الجغرافية.</p>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="present_country_id">الدولة / البلد</label>
                    <select class="form-control aiz-selectpicker text-right" name="present_country_id" id="present_country_id" data-selected="{{ $present_country_id }}" data-live-search="true">
                        <option value="">اختر الدولة..</option>
                        <?php $countries = \App\Models\Country::where('status',1)->get(); ?>
                        @foreach ($countries as $country)
                            <option value="{{$country->id}}">{{$country->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="present_state_id">المحافظة / الولاية</label>
                    <select class="form-control aiz-selectpicker text-right" name="present_state_id" id="present_state_id" data-live-search="true">
                        </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="present_city_id">المدينة</label>
                    <select class="form-control aiz-selectpicker text-right" name="present_city_id" id="present_city_id" data-live-search="true">
                        </select>
                </div>
                
                <input type="hidden" name="present_postal_code" value="{{$present_postal_code}}">
            </div>

            <div class="text-left mt-5 border-top pt-3">
                <button type="submit" class="btn btn-primary px-5 fw-800 shadow-primary">
                    حفظ ومتابعة <i class="las la-arrow-left mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>