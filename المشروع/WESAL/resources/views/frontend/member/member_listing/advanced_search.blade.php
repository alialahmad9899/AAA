<style>
    /* === التنسيقات النحيفة والسلسة لقائمة البحث (Slim & Smooth) === */
    .luxury-filter-sidebar {
        background: #ffffff !important;
        border-radius: 16px !important;
        border: 1px solid rgba(15, 23, 42, 0.04) !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03) !important;
    }
    
    .luxury-filter-sidebar .card-header {
        background: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding: 15px 20px !important;
    }

    .luxury-filter-sidebar .card-header h5 {
        font-weight: 800;
        color: var(--secondary);
        font-size: 15px;
        margin: 0;
    }

    .luxury-filter-label {
        font-weight: 700;
        color: #475569 !important; /* لون رمادي داكن مريح */
        font-size: 12.5px;
        margin-bottom: 6px;
    }

    /* حقول الإدخال النحيفة */
    .luxury-filter-input,
    .luxury-filter-sidebar .bootstrap-select .dropdown-toggle {
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #f8fafc !important;
        font-weight: 600;
        color: var(--secondary) !important;
        height: 42px !important; /* ارتفاع ملموم */
        font-size: 13px !important;
        box-shadow: none !important;
        padding-right: 12px !important;
        transition: all 0.2s ease-in-out;
    }

    .luxury-filter-sidebar .bootstrap-select .dropdown-toggle {
        padding-top: 10px !important; /* ضبط توسيط النص في القوائم المنسدلة */
    }

    .luxury-filter-input:focus,
    .luxury-filter-sidebar .bootstrap-select.show .dropdown-toggle {
        background-color: #ffffff !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1) !important; /* تحديد ذهبي ناعم جداً */
    }

    /* زر البحث النحيف والأنيق */
    .luxury-filter-btn {
        background: linear-gradient(135deg, var(--secondary), #1e293b) !important;
        color: var(--primary) !important;
        border-radius: 12px !important;
        font-weight: 800 !important;
        padding: 10px !important;
        border: none !important;
        font-size: 15px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15) !important;
        transition: all 0.3s ease;
    }

    .luxury-filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(15, 23, 42, 0.25) !important;
    }

    .luxury-filter-sidebar .separator {
        color: var(--primary) !important;
        font-weight: 800 !important;
        border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        padding-bottom: 8px;
        margin-top: 10px !important;
        margin-bottom: 15px !important;
        font-size: 12px;
    }

    /* تصغير مسافات الراديو لتكون ملمومة */
    .aiz-radio-list .aiz-radio {
        font-weight: 600;
        color: #334155;
        font-size: 13px;
        margin-bottom: 8px;
    }

    /* سكرول بار مخفي وأنيق للقائمة */
    .luxury-filter-sidebar.c-scrollbar-light::-webkit-scrollbar {
        width: 4px;
    }
    .luxury-filter-sidebar.c-scrollbar-light::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    
    .form-group { margin-bottom: 12px !important; } /* تقليل الفراغات بين الحقول */
</style>

<div class="aiz-filter-sidebar collapse-sidebar-wrap sidebar-xl z-1035">
    <div class="overlay overlay-fixed dark c-pointer" data-toggle="class-toggle" data-target=".aiz-filter-sidebar" data-same=".filter-sidebar-thumb"></div>
    <div class="card collapse-sidebar c-scrollbar-light shadow-none luxury-filter-sidebar text-right" dir="rtl">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="las la-filter mr-2" style="color: var(--primary); font-size: 20px;"></i>
                {{ translate('ADVANCED SEARCH') }}
            </h5>
            <button class="btn btn-sm p-1 d-xl-none filter-sidebar-thumb" data-toggle="class-toggle" data-target=".aiz-filter-sidebar" type="button" style="background: rgba(239, 68, 68, 0.1); border-radius: 8px;">
                <i class="las la-times fs-20 text-danger"></i>
            </button>
        </div>
        <div class="card-body p-3">
            <form action="{{ route('member.listing') }}" method="get">
                
                {{-- جمعنا العمر بصف واحد --}}
                <div class="row gutters-5">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label luxury-filter-label" for="age_from">{{ translate('Age From') }}</label>
                            <input type="number" name="age_from" value="{{ $age_from }}" class="form-control luxury-filter-input" placeholder="20">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label luxury-filter-label" for="age_to">{{ translate('To') }}</label>
                            <input type="number" name="age_to" value="{{ $age_to }}" class="form-control luxury-filter-input" placeholder="35">
                        </div>
                    </div>
                </div>

                {{-- الحقول الأساسية بدون مسافات إضافية --}}
                <div class="form-group">
                    <label class="form-label luxury-filter-label" for="name">{{ translate('Member ID') }}</label>
                    <input type="text" name="member_code" value="{{ $member_code }}" class="form-control luxury-filter-input" placeholder="أدخل رقم العضوية">
                </div>

                <div class="form-group">
                    <label class="form-label luxury-filter-label" for="name">{{ translate('Maritial Status') }}</label>
                    @php $marital_statuses = \App\Models\MaritalStatus::all(); @endphp
                    <select class="form-control aiz-selectpicker luxury-filter-input" name="marital_status" data-live-search="true">
                        <option value="">{{translate('Select One')}}</option>
                        @foreach ($marital_statuses as $marital_status)
                            <option value="{{$marital_status->id}}" @if($matital_status == $marital_status->id) selected @endif >{{$marital_status->name}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label luxury-filter-label" for="name">{{ translate('Religion') }}</label>
                    @php $religions = \App\Models\Religion::all(); @endphp
                    <select name="religion_id" id="religion_id" class="form-control aiz-selectpicker luxury-filter-input"  data-live-search="true" >
                        <option value="">{{translate('Choose One')}}</option>
                        @foreach ($religions as $religion)
                            <option value="{{ $religion->id }}" @if($religion->id == $religion_id) selected @endif> {{ $religion->name }} </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label luxury-filter-label" for="name">{{ translate('Profession') }}</label>
                    <input type="text" name="profession" value="{{ $profession }}" class="form-control luxury-filter-input" placeholder="المهنة أو الوظيفة">
                </div>

                <div class="form-group">
                    <label class="form-label luxury-filter-label" for="name">{{ translate('Country') }}</label>
                    @php $countries = \App\Models\Country::where('status',1)->get(); @endphp
                    <select name="country_id" id="country_id" class="form-control aiz-selectpicker luxury-filter-input" data-live-search="true" >
                        <option value="">{{translate('Select One')}}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @if($country->id == $country_id) selected @endif >{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label luxury-filter-label" for="name">{{ translate('State') }}</label>
                    <select name="state_id" id="state_id" class="form-control aiz-selectpicker luxury-filter-input" data-live-search="true" >
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label luxury-filter-label" for="name">{{ translate('City') }}</label>
                    <select name="city_id" id="city_id" class="form-control aiz-selectpicker luxury-filter-input" data-live-search="true" >
                    </select>
                </div>

                {{-- جمعنا الطول بصف واحد --}}
                <div class="row gutters-5">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label luxury-filter-label" for="name">{{ translate('Min Height') }}</label>
                            <input type="number" name="min_height" value="{{ $min_height }}" class="form-control luxury-filter-input" min="0" step="0.01" placeholder="150">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label luxury-filter-label" for="name">{{ translate('Max Height') }}</label>
                            <input type="number" name="max_height" value="{{ $max_height }}" class="form-control luxury-filter-input" min="0" step="0.01" placeholder="190">
                      </div>
                    </div>
                </div>

                <h6 class="separator">
                    <span>{{ translate('Member Type') }}</span>
                </h6>

                <div class="aiz-radio-list pl-0 pr-1">
                    <label class="aiz-radio">
                        <input type="radio" name="member_type" value="2" onchange="applyFilter()" @if($member_type == 2) checked @endif > {{ translate('Premium Member') }}
                        <span class="aiz-rounded-check"></span>
                    </label>
                    <label class="aiz-radio">
                        <input type="radio" name="member_type" value="1" onchange="applyFilter()"  @if($member_type == 1) checked @endif > {{ translate('Free member') }}
                        <span class="aiz-rounded-check"></span>
                    </label>
                    <label class="aiz-radio">
                        <input type="radio" name="member_type" value="0" @if($member_type == 0) checked @endif> {{ translate('All Member') }}
                        <span class="aiz-rounded-check"></span>
                    </label>
                </div>

                <button type="submit" class="btn btn-block mt-3 luxury-filter-btn">
                    <i class="las la-search fs-18"></i> {{ translate('Search') }}
                </button>
            </form>
        </div>
    </div>
</div>