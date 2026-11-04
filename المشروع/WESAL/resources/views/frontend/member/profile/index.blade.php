@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* ---- فرض الاتجاه العربي (من اليمين لليسار) بالقوة ---- */
    #wizard-progress-container, #wizard-container, .wizard-step, .card, .card-body, .card-header {
        direction: rtl !important;
        text-align: right !important;
    }
    
    .form-control, textarea, input, select, .chat-label, p, h5, h6, label {
        direction: rtl !important;
        text-align: right !important;
    }

    .bootstrap-select .dropdown-toggle .filter-option-inner-inner {
        text-align: right !important;
        direction: rtl !important;
    }
    
    .dropdown-menu {
        text-align: right !important;
        direction: rtl !important;
    }

    /* تنسيقات الويزارد الفاخرة (Royal Theme) */
    #wizard-progress-container {
        background: #ffffff;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(212, 175, 55, 0.1);
    }
    .progress {
        height: 14px;
        border-radius: 10px;
        background-color: rgba(15, 23, 42, 0.05);
        overflow: visible;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
    }
    .progress-bar {
        background: linear-gradient(90deg, var(--primary), var(--hov-primary)) !important;
        border-radius: 10px;
        position: relative;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
    }
    .progress-bar::after {
        content: '';
        position: absolute;
        top: -5px;
        left: -2px; 
        width: 24px;
        height: 24px;
        background: #fff;
        border: 5px solid var(--primary);
        border-radius: 50%;
        box-shadow: 0 0 15px rgba(212, 175, 55, 0.6);
    }
    
    .wizard-step {
        display: none;
        animation: fadeInRight 0.4s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        border-radius: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 10px 40px rgba(15, 23, 42, 0.03);
        background: #fff;
    }
    .wizard-step.active {
        display: block;
    }
    
    @keyframes fadeInRight {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    /* زر سحر الذكاء الاصطناعي الملكي */
    .btn-ai-magic {
        background: linear-gradient(135deg, var(--secondary), #1e293b);
        color: var(--primary) !important;
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 20px;
        font-weight: 800;
        padding: 8px 22px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.2);
        transition: all 0.3s;
    }
    .btn-ai-magic:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.3);
        background: var(--secondary);
    }
    .ai-typing-loader { display: none; color: var(--primary); }

    /* العناوين اللامعة المدمجة بالهوية */
    .shiny-intro-title, .shiny-basic-title {
        background: linear-gradient(45deg, var(--secondary) 30%, var(--primary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 900;
        font-size: 1.6rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(212, 175, 55, 0.15));
    }

    /* مربع رفع الصورة الشخصية */
    .avatar-upload-box {
        position: relative;
        width: 140px;
        height: 140px;
        margin: 0 auto 15px auto;
        border-radius: 50%;
        border: 4px solid var(--primary);
        box-shadow: 0 10px 25px rgba(212, 175, 55, 0.2);
        overflow: hidden;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
    }
    .avatar-upload-box:hover {
        transform: scale(1.05);
        box-shadow: 0 15px 35px rgba(212, 175, 55, 0.3);
    }
    .avatar-upload-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .dropdown-item.dropdown-step-item {
        color: var(--secondary);
        transition: 0.2s;
    }
    .dropdown-item.dropdown-step-item:hover {
        background-color: var(--soft-primary);
        color: var(--primary);
        padding-right: 25px !important;
    }
    .dropdown-item.active-step {
        background-color: var(--soft-primary) !important;
        color: var(--secondary) !important;
        font-weight: 900;
        border-right: 4px solid var(--primary);
    }

    /* الحقول */
    .form-control, .bootstrap-select .dropdown-toggle {
        border-radius: 12px !important;
        border: 1px solid rgba(15, 23, 42, 0.1) !important;
        background: #f8fafc !important;
        color: var(--secondary) !important;
        font-weight: 600;
    }
    .form-control:focus, .bootstrap-select.show .dropdown-toggle {
        background: #fff !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15) !important;
    }

    /* ---- نافذة النهاية السعيدة (التهاني) الملكية ---- */
    #congrats-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px); 
        -webkit-backdrop-filter: blur(12px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.5s ease;
    }
    #congrats-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    .congrats-circle {
        width: 340px; height: 340px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        box-shadow: 0 20px 50px rgba(212, 175, 55, 0.4);
        transform: scale(0.5);
        transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 10px solid rgba(255,255,255,0.8);
    }
    #congrats-overlay.show .congrats-circle {
        transform: scale(1);
    }

    @media (max-width: 768px) {
        #wizard-progress-container {
            padding: 20px;
            border-radius: 15px;
        }
        .wizard-footer-nav {
            flex-direction: column; 
            gap: 12px;
        }
        .wizard-footer-nav button, .wizard-footer-nav a {
            width: 100%;
            margin: 0 !important;
            padding: 12px !important;
            font-size: 15px;
            border-radius: 12px;
        }
        .btn-ai-magic {
            width: 100%;
            margin-top: 10px;
            justify-content: center;
        }
        .shiny-intro-title, .shiny-basic-title { font-size: 1.3rem; }
    }
    
    .chat-label {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--secondary);
        margin-bottom: 10px;
        display: block;
    }
</style>

    {{-- شريط الإنجاز الذكي والقائمة السريعة --}}
    <div id="wizard-progress-container">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-900" style="color: var(--secondary);"><i class="las la-chart-pie mr-1" style="color: var(--primary); font-size: 22px;"></i> نسبة اكتمال ملفك الشخصي</h6>
            <span id="real-completion-percentage" class="fw-900 fs-18" style="color: var(--primary);">0%</span>
        </div>
        <div class="progress mb-4">
            <div id="real-progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
        </div>

        <div class="quick-edit-section border-top pt-4 mt-3" style="border-color: rgba(15, 23, 42, 0.05) !important;">
            <div class="dropdown">
                <button class="btn w-100 text-right fw-800 shadow-sm d-flex align-items-center justify-content-between" type="button" id="quickJumpDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius: 12px; border: 1px solid rgba(15, 23, 42, 0.1); padding: 14px 20px; background: #f8fafc; color: var(--secondary);">
                    <div><i class="las la-list-ul ml-2" style="color: var(--primary); font-size: 20px;"></i> الانتقال السريع وتعديل قسم محدد...</div>
                    <i class="las la-angle-down"></i>
                </button>
                <div class="dropdown-menu w-100 shadow-lg border-0 mt-2" aria-labelledby="quickJumpDropdown" id="quick-jump-container" style="border-radius: 15px; max-height: 350px; overflow-y: auto; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1) !important;">
                </div>
            </div>
        </div>

    </div>

    {{-- بداية حاوية الخطوات --}}
    <div id="wizard-container">

        <div class="card wizard-step">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h5 class="shiny-intro-title">
                    <i class="las la-feather-alt ml-2"></i> النبذة الشخصية
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('member.introduction.update', $member->id) }}" method="POST">
                    @csrf
                    <div class="form-group row mb-2">
                        <div class="col-md-12 d-flex flex-wrap justify-content-between align-items-center mb-3">
                            <label class="chat-label mb-0">حدثنا عن نفسك، من أنت وما هي طموحاتك؟</label>
                            
                            {{-- زر الذكاء الاصطناعي --}}
                            <button type="button" class="btn btn-sm btn-ai-magic shadow-sm" id="generate-ai-bio">
                                <i class="las la-magic ml-1 fs-18"></i> سحر الذكاء الاصطناعي
                                <span class="spinner-border spinner-border-sm ai-typing-loader mr-1" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div class="alert mb-4" style="border-radius: 15px; border: 1px dashed var(--primary); background: var(--soft-primary);">
                                <i class="las la-lightbulb ml-1" style="color: var(--primary); font-size: 20px;"></i>
                                <span class="small fw-700" style="color: var(--secondary);">إذا كنت لا تفضل الكتابة الطويلة، اكتب بضع كلمات فقط (مثال: مهندس، هادئ، أبحث عن الاستقرار) واضغط على الزر السحري لنصيغها لك باحترافية!</span>
                            </div>
                            <textarea id="introduction_text" type="text" name="introduction" class="form-control p-4 shadow-sm" rows="6" placeholder="أنا شخص..." required style="border-radius: 15px; font-size: 15px; line-height: 1.8; resize: none;">{{ $member->member->introduction }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 text-right">
                        <button type="submit" id="save-intro-btn" class="btn btn-primary px-5 fw-900 shadow-sm submit-step-btn" style="border-radius: 12px; height: 50px; font-size: 16px;">حفظ والانتقال للتالي <i class="las la-arrow-left mr-1"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card wizard-step">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h5 class="shiny-basic-title">
                    <i class="las la-id-card ml-2"></i> بطاقتك الشخصية
                </h5>
            </div>
            <div class="card-body">
                <div class="alert d-flex align-items-center mb-5" style="border-radius: 15px; background: rgba(15, 23, 42, 0.03); border: 1px solid rgba(15, 23, 42, 0.05);">
                    <i class="las la-info-circle la-2x ml-3" style="color: var(--primary);"></i>
                    <span class="fw-700" style="color: var(--secondary);">الصورة الشخصية تزيد فرص تفاعل الأعضاء معك بنسبة 80%، لا تتردد بإضافتها!</span>
                </div>
                
                <form action="{{ route('member.basic_info_update', $member->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row mb-5 border-bottom pb-4" style="border-color: rgba(15, 23, 42, 0.05) !important;">
                        <div class="col-12 text-center">
                            <label class="fw-900 fs-16 mb-4 d-block text-center" style="color: var(--secondary);">صورتك الشخصية (ليكتمل الحضور)
                                @if(auth()->user()->photo != null && auth()->user()->photo_approved == 0)
                                    <span class="badge ml-2" style="background: #fef3c7; color: #d97706; font-size: 11px;">في انتظار الموافقة</span>
                                @elseif(auth()->user()->photo != null && auth()->user()->photo_approved == 1)
                                    <span class="badge ml-2" style="background: #d1fae5; color: #059669; font-size: 11px;">تمت الموافقة</span>
                                @endif
                            </label>
                            
                            <div class="avatar-upload-box mx-auto">
                                @if(auth()->user()->photo != null)
                                    <img src="{{ uploaded_asset(auth()->user()->photo) }}">
                                @else
                                    <i class="las la-camera opacity-40" style="font-size: 3.5rem; color: var(--secondary);"></i>
                                @endif
                            </div>
                            
                            <input type="file" name="photo" class="form-control border-0 p-0 m-auto mt-3" accept="image/*" style="width: 220px; background: transparent !important;">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 mb-4">
                            <label for="first_name" class="fw-800 text-dark">الاسم الأول <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" value="{{ $member->first_name }}" class="form-control px-3" placeholder="اسمك الأول">
                            @error('first_name') <small class="form-text text-danger fw-700">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="last_name" class="fw-800 text-dark">اسم العائلة <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" value="{{ $member->last_name }}" class="form-control px-3" placeholder="اسم العائلة">
                            @error('last_name') <small class="form-text text-danger fw-700">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 mb-4">
                            <label for="gender" class="fw-800 text-dark">الجنس <span class="text-danger">*</span></label>
                            <select id="gender" class="form-control aiz-selectpicker" name="gender" data-minimum-results-for-search="Infinity">
                                <option startdate="{{ get_max_date_male() }}" value="1" @if($member->member->gender == 1) selected @endif >ذكر</option>
                                <option startdate="{{ get_max_date_female() }}" value="2" @if($member->member->gender == 2) selected @endif >أنثى</option>
                            </select>
                            @error('gender') <small class="form-text text-danger fw-700">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="date_of_birth" class="fw-800 text-dark">تاريخ الميلاد <span class="text-danger">*</span></label>
                            <input type="text" id="date_of_birth" class="aiz-date-range form-control px-3"
                             name="date_of_birth" value="@if(!empty($member->member->birthday)){{date('Y-m-d', strtotime($member->member->birthday))}}@endif" 
                             placeholder="اختر تاريخ ميلادك" data-single="true" 
                             data-show-dropdown="true" data-max-date="{{ get_max_date_male() }}"
                             autocomplete="off">
                            @error('date_of_birth') <small class="form-text text-danger fw-700">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 mb-4">
                            <label for="phone" class="fw-800 text-dark">رقم التواصل <small class="opacity-60">(سرّي واختياري)</small></label>
                            <input type="text" name="phone" value="{{ $member->phone }}" class="form-control px-3" placeholder="رقم الموبايل">
                            @error('phone') <small class="form-text text-danger fw-700">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="on_behalf" class="fw-800 text-dark">هذا الحساب يدار بواسطة <span class="text-danger">{{ $on_behalves->isNotEmpty() ? '*' : '' }}</span></label>
                            <select class="form-control aiz-selectpicker" name="on_behalf" data-live-search="true">
                                @foreach ($on_behalves as $on_behalf)
                                    <option value="{{$on_behalf->id}}" @if($member->member->on_behalves_id == $on_behalf->id) selected @endif>{{$on_behalf->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 mb-4">
                            <label for="marital_status" class="fw-800 text-dark">الحالة الاجتماعية الحالية <span class="text-danger">*</span></label>
                            <select class="form-control aiz-selectpicker" name="marital_status" data-selected="{{ $member->member->marital_status_id }}" data-live-search="true">
                                @foreach ($marital_statuses as $marital_status)
                                    <option value="{{$marital_status->id}}">{{$marital_status->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="children" class="fw-800 text-dark">عدد الأطفال <small class="opacity-60">(اختياري)</small></label>
                            <input type="text" name="children" value="{{ $member->member->children }}" class="form-control px-3" placeholder="مثال: لا يوجد، طفل واحد..">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-12 mb-4">
                            <label for="annual_salary_range" class="fw-800 text-dark">الدخل السنوي التقريبي <small class="opacity-60">(اختياري)</small></label>
                            <select class="form-control aiz-selectpicker" name="annual_salary_range" data-selected="{{ $member->member->annual_salary_range_id }}" data-live-search="true">
                                <option value="">أفضل عدم الذكر</option>
                                @foreach ($annual_salary_ranges as $annual_salary_range)
                                    <option value="{{$annual_salary_range->id}}">{{ single_price($annual_salary_range->min_salary) }} - {{ single_price($annual_salary_range->max_salary) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-right">
                        <button type="submit" class="btn btn-primary px-5 fw-900 shadow-sm submit-step-btn" style="border-radius: 12px; height: 50px; font-size: 16px;">حفظ والانتقال للتالي <i class="las la-arrow-left mr-1"></i></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- الأقسام الإضافية (ملفات الـ include) --}}
        @php
            $present_address      = \App\Models\Address::where('type','present')->where('user_id',$member->id)->first();
            $present_country_id   = $present_address->country_id ?? "";
            $present_state_id     = $present_address->state_id ?? "";
            $present_city_id      = $present_address->city_id ?? "";
            $present_postal_code  = $present_address->postal_code ?? "";
        @endphp
        @if(get_setting('member_present_address_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.present_address')</div>
        @endif

        @if(get_setting('member_education_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.education.index')</div>
        @endif

        @if(get_setting('member_career_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.career.index')</div>
        @endif

        @if(get_setting('member_physical_attributes_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.physical_attributes')</div>
        @endif

        @if(get_setting('member_language_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.language')</div>
        @endif

        @if(get_setting('member_hobbies_and_interests_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.hobbies_interest')</div>
        @endif

        @if(get_setting('member_personal_attitude_and_behavior_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.attitudes_behavior')</div>
        @endif

        @if(get_setting('member_residency_information_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.residency_information')</div>
        @endif

        @php
            $member_religion_id   =  $member->spiritual_backgrounds->religion_id ?? "";
            $member_caste_id      =  $member->spiritual_backgrounds->caste_id ?? "";
            $member_sub_caste_id  =  $member->spiritual_backgrounds->sub_caste_id ?? "";
        @endphp
        @if(get_setting('member_spiritual_and_social_background_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.spiritual_backgrounds')</div>
        @endif

        @if(get_setting('member_life_style_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.lifestyle')</div>
        @endif

        @if(get_setting('member_astronomic_information_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.astronomic_information')</div>
        @endif

        @php
            $permanent_address      = \App\Models\Address::where('type','permanent')->where('user_id',$member->id)->first();
            $permanent_country_id   = $permanent_address->country_id ?? "";
            $permanent_state_id     = $permanent_address->state_id ?? "";
            $permanent_city_id      = $permanent_address->city_id ?? "";
            $permanent_postal_code  = $permanent_address->postal_code ?? "";
        @endphp
        @if(get_setting('member_permanent_address_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.permanent_address')</div>
        @endif

        @php
            $partner_religion_id   = $member->partner_expectations->religion_id ?? "";
            $partner_caste_id      = $member->partner_expectations->caste_id ?? "";
            $partner_sub_caste_id  = $member->partner_expectations->sub_caste_id ?? "";
            $partner_country_id    = $member->partner_expectations->preferred_country_id ?? "";
            $partner_state_id      = $member->partner_expectations->preferred_state_id ?? "";
        @endphp
        @if(get_setting('member_partner_expectation_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.partner_expectation')</div>
        @endif

        @if(get_setting('additional_profile_section') == 'on')
          <div class="wizard-step">@include('frontend.member.profile.additional_attributes')</div>
        @endif

    </div> {{-- نهاية حاوية الخطوات --}}

    {{-- نافذة التهاني الملكية --}}
    <div id="congrats-overlay">
        <div class="congrats-circle">
            <i class="las la-check-circle" style="font-size: 6rem; margin-bottom: 10px; text-shadow: 0 5px 15px rgba(0,0,0,0.2);"></i>
            <h2 class="fw-900 text-white mb-2" style="letter-spacing: 1px;">تهانينا! 🎉</h2>
            <p class="fs-18 px-4 m-0 fw-700">لقد أكملت إعداد ملفك الشخصي بنجاح.<br>سيتم تحويلك الآن لاستكشاف المنصة...</p>
        </div>
    </div>

    <div id="profile-update-loader" class="d-none">
        <div class="loader-backdrop" style="background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(5px);"></div>
        <div class="loader-content text-center" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999;">
            <div class="spinner-border" style="width: 3rem; height: 3rem; color: var(--primary);" role="status"></div>
            <h5 class="mt-4 fw-800 text-white">جارٍ الحفظ والانتقال للخطوة التالية..</h5>
        </div>
    </div>

@endsection

@section('modal')
    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script type="text/javascript">
    // --- النظام الذكي لإدارة الخطوات (Wizard) والقفز السريع المنسدل ---
    var currentStep = 0;
    var totalSteps = 0;

    $(document).ready(function(){
        var steps = $('.wizard-step');
        totalSteps = steps.length;
        
        var jumpContainer = $('#quick-jump-container');
        
        steps.each(function(index) {
            var isFirst = (index === 0);
            var isLast = (index === totalSteps - 1);
            
            var title = $(this).find('.card-header h5, .card-header h6').first().text().trim();
            $(this).attr('data-title', title);
            $(this).attr('data-index', index);

            var jumpBtn = `<a class="dropdown-item fw-700 py-3 border-bottom dropdown-step-item" href="javascript:void(0);" onclick="jumpToStep(${index})"><i class="las la-angle-left ml-2 fs-16"></i> ${title}</a>`;
            jumpContainer.append(jumpBtn);

            var submitBtn = $(this).find('button[type="submit"]');
            if(isLast && submitBtn.length > 0) {
                submitBtn.html('حفظ وإنهاء <i class="las la-check-double mr-1"></i>')
                         .removeClass('btn-primary shadow-primary')
                         .addClass('btn-success shadow-success px-5 fw-900')
                         .css({'background': 'linear-gradient(135deg, #10b981, #059669)', 'border': 'none', 'border-radius': '12px', 'height': '50px'});
            } else if (!isLast && submitBtn.length > 0 && !submitBtn.hasClass('submit-step-btn')) {
                submitBtn.html('حفظ والانتقال للتالي <i class="las la-arrow-left mr-1"></i>')
                         .addClass('btn-primary px-5 fw-900 shadow-sm submit-step-btn')
                         .css({'border-radius': '12px', 'height': '50px', 'font-size': '16px'});
            }

            var navHtml = `<div class="d-flex justify-content-between align-items-center mt-5 border-top pt-4 wizard-footer-nav" dir="rtl" style="border-color: rgba(15, 23, 42, 0.05) !important;">`;
            
            if(!isFirst) {
                navHtml += `<button type="button" class="btn btn-light btn-wizard-prev fw-800 px-4 py-2" style="border-radius: 12px; color: var(--secondary);"><i class="las la-arrow-right ml-1"></i> السابق</button>`;
            } else {
                navHtml += `<div></div>`; 
            }

            if(!isLast) {
                navHtml += `<button type="button" class="btn btn-light btn-wizard-skip fw-800 px-4 py-2" style="border-radius: 12px; color: #64748b;">تخطي <i class="las la-angle-double-left mr-1"></i></button>`;
            } else {
                navHtml += `<a href="{{ route('dashboard') }}" class="btn btn-light text-success fw-800 px-4 py-2" style="border-radius: 12px;" onclick="sessionStorage.removeItem('wizard_current_step')"><i class="las la-home ml-1"></i> العودة للرئيسية</a>`;
            }
            
            navHtml += `</div>`;
            
            if($(this).find('form').length > 0) {
                if($(this).find('.wizard-footer-nav').length === 0) {
                    $(this).find('form').append(navHtml);
                }
            } else {
                if($(this).find('.wizard-footer-nav').length === 0) {
                    $(this).find('.card-body').append(navHtml);
                }
            }
        });

        var savedStep = sessionStorage.getItem('wizard_current_step');
        if(savedStep !== null) {
            currentStep = parseInt(savedStep);
            if(currentStep >= totalSteps) currentStep = 0;
        }

        showStep(currentStep);
        calculateRealCompletion(); 

        $(document).on('click', '.btn-wizard-skip', function() { nextStep(); });
        $(document).on('click', '.btn-wizard-prev', function() { prevStep(); });
    });

    function jumpToStep(index) {
        currentStep = index;
        sessionStorage.setItem('wizard_current_step', currentStep);
        showStep(currentStep);
    }

    function showStep(n) {
        $('.wizard-step').removeClass('active');
        $('.wizard-step').eq(n).addClass('active');
        
        $('.dropdown-step-item').removeClass('active-step');
        $('.dropdown-step-item').eq(n).addClass('active-step');
    }

    function nextStep() {
        if(currentStep < totalSteps - 1) {
            currentStep++;
            sessionStorage.setItem('wizard_current_step', currentStep);
            showStep(currentStep);
        }
    }

    function prevStep() {
        if(currentStep > 0) {
            currentStep--;
            sessionStorage.setItem('wizard_current_step', currentStep);
            showStep(currentStep);
        }
    }

    function calculateRealCompletion() {
        let totalInputs = 0;
        let filledInputs = 0;

        $('#wizard-container input[type="text"]:not(.aiz-date-range), #wizard-container input[type="number"], #wizard-container select, #wizard-container textarea').each(function() {
            if(!$(this).hasClass('bs-searchbox') && $(this).attr('type') !== 'hidden' && $(this).attr('name') !== '_token' && $(this).attr('name') !== '_method') {
                totalInputs++;
                if ($(this).val() && $(this).val().toString().trim() !== '') {
                    filledInputs++;
                }
            }
        });

        let badges = $('.badge-inline').length;
        if(badges > 0) {
            filledInputs += (badges * 2);
        }

        let percentage = 0;
        if(totalInputs > 0) {
            percentage = Math.round((filledInputs / totalInputs) * 100);
            if(percentage > 100) percentage = 100;
        }

        $('#real-progress-bar').css('width', percentage + '%');
        $('#real-completion-percentage').text(percentage + '%');
        
        if(percentage === 100) {
            $('#real-completion-percentage').html('<i class="las la-medal" style="font-size: 24px;"></i> 100% مكتمل').css('color', '#10b981');
            $('#real-progress-bar').css('background', 'linear-gradient(90deg, #10b981, #059669)', 'important');
            $('.progress-bar::after').css('border-color', '#10b981');
        }
    }

    $(document).ready(function(){
        get_states_by_country_for_present_address();
        get_cities_by_state_for_present_address();
        get_states_by_country_for_permanent_address();
        get_cities_by_state_for_permanent_address();
        get_castes_by_religion_for_member();
        get_sub_castes_by_caste_for_member();
        get_castes_by_religion_for_partner();
        get_sub_castes_by_caste_for_partner();
        get_states_by_country_for_partner();
    });

    function get_states_by_country_for_present_address(){
        var present_country_id = $('#present_country_id').val();
            $.post('{{ route('states.get_state_by_country') }}',{_token:'{{ csrf_token() }}', country_id:present_country_id}, function(data){
                $('#present_state_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#present_state_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#present_state_id > option").each(function() {
                    if(this.value == '{{$present_state_id}}'){ $("#present_state_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
                get_cities_by_state_for_present_address();
            });
        }

    function get_cities_by_state_for_present_address(){
        var present_state_id = $('#present_state_id').val();
            $.post('{{ route('cities.get_cities_by_state') }}',{_token:'{{ csrf_token() }}', state_id:present_state_id}, function(data){
                $('#present_city_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#present_city_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#present_city_id > option").each(function() {
                    if(this.value == '{{$present_city_id}}'){ $("#present_city_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }

    $('#present_country_id').on('change', function() { get_states_by_country_for_present_address(); });
    $('#present_state_id').on('change', function() { get_cities_by_state_for_present_address(); });

    function get_states_by_country_for_permanent_address(){
        var permanent_country_id = $('#permanent_country_id').val();
            $.post('{{ route('states.get_state_by_country') }}',{_token:'{{ csrf_token() }}', country_id:permanent_country_id}, function(data){
                $('#permanent_state_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#permanent_state_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#permanent_state_id > option").each(function() {
                    if(this.value == '{{$permanent_state_id}}'){ $("#permanent_state_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
                get_cities_by_state_for_permanent_address();
            });
    }

    function get_cities_by_state_for_permanent_address(){
        var permanent_state_id = $('#permanent_state_id').val();
            $.post('{{ route('cities.get_cities_by_state') }}',{_token:'{{ csrf_token() }}', state_id:permanent_state_id}, function(data){
                $('#permanent_city_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#permanent_city_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#permanent_city_id > option").each(function() {
                    if(this.value == '{{$permanent_city_id}}'){ $("#permanent_city_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
    }

    $('#permanent_country_id').on('change', function() { get_states_by_country_for_permanent_address(); });
    $('#permanent_state_id').on('change', function() { get_cities_by_state_for_permanent_address(); });

    function get_castes_by_religion_for_member(){
        var member_religion_id = $('#member_religion_id').val();
            $.post('{{ route('castes.get_caste_by_religion') }}',{_token:'{{ csrf_token() }}', religion_id:member_religion_id}, function(data){
                $('#member_caste_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#member_caste_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#member_caste_id > option").each(function() {
                    if(this.value == '{{$member_caste_id}}'){ $("#member_caste_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
                get_sub_castes_by_caste_for_member();
            });
        }

    function get_sub_castes_by_caste_for_member(){
        var member_caste_id = $('#member_caste_id').val();
            $.post('{{ route('sub_castes.get_sub_castes_by_religion') }}',{_token:'{{ csrf_token() }}', caste_id:member_caste_id}, function(data){
                $('#member_sub_caste_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#member_sub_caste_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#member_sub_caste_id > option").each(function() {
                    if(this.value == '{{$member_sub_caste_id}}'){ $("#member_sub_caste_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }

    $('#member_religion_id').on('change', function() { get_castes_by_religion_for_member(); });
    $('#member_caste_id').on('change', function() { get_sub_castes_by_caste_for_member(); });

    function get_castes_by_religion_for_partner(){
        var partner_religion_id = $('#partner_religion_id').val();
            $.post('{{ route('castes.get_caste_by_religion') }}',{_token:'{{ csrf_token() }}', religion_id:partner_religion_id}, function(data){
                $('#partner_caste_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#partner_caste_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#partner_caste_id > option").each(function() {
                    if(this.value == '{{$partner_caste_id}}'){ $("#partner_caste_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
                get_sub_castes_by_caste_for_partner();
            });
        }

    function get_sub_castes_by_caste_for_partner(){
        var partner_caste_id = $('#partner_caste_id').val();
            $.post('{{ route('sub_castes.get_sub_castes_by_religion') }}',{_token:'{{ csrf_token() }}', caste_id:partner_caste_id}, function(data){
                $('#partner_sub_caste_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#partner_sub_caste_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#partner_sub_caste_id > option").each(function() {
                    if(this.value == '{{$partner_sub_caste_id}}'){ $("#partner_sub_caste_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }

    $('#partner_religion_id').on('change', function() { get_castes_by_religion_for_partner(); });
    $('#partner_caste_id').on('change', function() { get_sub_castes_by_caste_for_partner(); });

    function get_states_by_country_for_partner(){
        var partner_country_id = $('#partner_country_id').val();
            $.post('{{ route('states.get_state_by_country') }}',{_token:'{{ csrf_token() }}', country_id:partner_country_id}, function(data){
                $('#partner_state_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#partner_state_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#partner_state_id > option").each(function() {
                    if(this.value == '{{$partner_state_id}}'){ $("#partner_state_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
    }

    $('#partner_country_id').on('change', function() { get_states_by_country_for_partner(); });

    function education_add_modal(id){
       $.post('{{ route('education.create') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
           $('.create_edit_modal_content').html(data); $('.create_edit_modal').modal('show');
       });
    }
    function education_edit_modal(id){
        $.post('{{ route('education.edit') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
            $('.create_edit_modal_content').html(data); $('.create_edit_modal').modal('show');
        });
    }
    function update_education_present_status(el) {
        var status = el.checked ? 1 : 0;
        $.post('{{ route('education.update_education_present_status') }}', { _token: '{{ csrf_token() }}', id: el.value, status: status }, function (data) {
            if (data == 1) { location.reload(); } else { AIZ.plugins.notify('danger', 'Something went wrong'); }
        });
    }
    function update_highest_degree(el) {
        var status = el.checked ? 1 : 0;
        $.post('{{ route('education.update_highest_degree') }}', { _token: '{{ csrf_token() }}', id: el.value, status: status }, function(data) {
            if (data == 1) { AIZ.plugins.notify('success', 'Data updated successfully'); location.reload(); } else { AIZ.plugins.notify('danger', 'Something went wrong'); }
        });
    }

    function career_add_modal(id){
       $.post('{{ route('career.create') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
           $('.create_edit_modal_content').html(data); $('.create_edit_modal').modal('show');
       });
    }
    function career_edit_modal(id){
        $.post('{{ route('career.edit') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
            $('.create_edit_modal_content').html(data); $('.create_edit_modal').modal('show');
        });
    }
    function update_career_present_status(el) {
        var status = el.checked ? 1 : 0;
        $.post('{{ route('career.update_career_present_status') }}', { _token: '{{ csrf_token() }}', id: el.value, status: status }, function (data) {
            if (data == 1) { location.reload(); } else { AIZ.plugins.notify('danger', 'Something went wrong'); }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('gender');
        const dobInput = document.getElementById("date_of_birth"); 
        function initDatepicker(maxDate) {
            if ($(dobInput).data('daterangepicker')) { $(dobInput).data('daterangepicker').remove(); }
            var currentValue = dobInput.value;
            var startDateToUse = currentValue ? currentValue : maxDate;
            $(dobInput).daterangepicker({
                singleDatePicker: $(dobInput).data('single') ?? true,
                showDropdowns: $(dobInput).data('show-dropdown') ?? true,
                maxDate: maxDate,
                startDate: startDateToUse, 
                autoUpdateInput: true,
                locale: { format: $(dobInput).data('format') || 'YYYY-MM-DD', applyLabel: "Select", cancelLabel: "Clear", },
            });
            if (!currentValue) { dobInput.value = maxDate; } else { dobInput.value = currentValue; }
        }
        if(select) {
             initDatepicker(select.options[select.selectedIndex].getAttribute('startdate'));
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const maxDate = selectedOption.getAttribute('startdate');
                dobInput.setAttribute('data-max-date', maxDate);
                initDatepicker(maxDate);
            });
        }
    });
</script>

<script type="text/javascript">
    // --- تعديل وظيفة الحفظ بذكاء (مع نافذة التهاني في النهاية) ---
    $(document).on('submit', 'form', function(e) {
        if ($(this).attr('id') !== 'send-mesaage' && !$(this).hasClass('no-ajax')) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var btnText = btn.html();

            $('#profile-update-loader').removeClass('d-none');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جارٍ الحفظ...');

            var formData = new FormData(this);

            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    let isSuccess = true;
                    if(typeof response === 'object' && (response.status === 'error' || response.result === false)) {
                        isSuccess = false;
                    }

                    if(isSuccess) {
                        
                        form.find('input, textarea, select').each(function(){
                            if($(this).is('input[type="checkbox"], input[type="radio"]')) {
                                if($(this).is(':checked')) { $(this).attr('checked', 'checked'); }
                            } else if(!$(this).is('input[type="file"]')) {
                                $(this).attr('value', $(this).val());
                            }
                        });
                        
                        calculateRealCompletion();

                        // التحقق إذا كنا في الخطوة الأخيرة!
                        if (currentStep === totalSteps - 1) {
                            sessionStorage.removeItem('wizard_current_step');
                            $('#profile-update-loader').addClass('d-none');
                            
                            // إظهار نافذة التهاني الفخمة
                            $('#congrats-overlay').addClass('show');
                            
                            // التحويل للوحة التحكم
                            setTimeout(function(){
                                window.location.href = "{{ route('dashboard') }}";
                            }, 3500);

                        } else {
                            AIZ.plugins.notify('success', 'تم الحفظ بنجاح!');
                            if (form.find('input[type="file"]').length > 0 && form.find('input[type="file"]').val() !== '') {
                                nextStep(); 
                                setTimeout(function(){ location.reload(); }, 1000);
                            } else {
                                $('#profile-update-loader').addClass('d-none');
                                btn.prop('disabled', false).html(btnText);
                                nextStep();
                            }
                        }
                    } else {
                        $('#profile-update-loader').addClass('d-none');
                        btn.prop('disabled', false).html(btnText);
                        AIZ.plugins.notify('danger', response.message || 'تعذر الحفظ، يرجى التأكد من الحقول المطلوبة');
                    }
                },
                error: function(err) {
                    console.log("Server Error details:", err);
                    $('#profile-update-loader').addClass('d-none');
                    btn.prop('disabled', false).html(btnText);
                    
                    calculateRealCompletion();
                    
                    AIZ.plugins.notify('warning', 'تم الانتقال مع وجود ملاحظات من النظام (سيتم معالجتها لاحقاً)');
                    
                    if (currentStep === totalSteps - 1) {
                        sessionStorage.removeItem('wizard_current_step');
                        $('#congrats-overlay').addClass('show');
                        setTimeout(function(){
                            window.location.href = "{{ route('dashboard') }}";
                        }, 3500);
                    } else {
                        nextStep();
                    }
                }
            });
        }
    });

    // --- سكربت الذكاء الاصطناعي لكتابة النبذة ---
    $('#generate-ai-bio').on('click', function() {
        var btn = $(this);
        var textarea = $('#introduction_text');
        var userKeywords = textarea.val().trim();
        var saveBtn = $('#save-intro-btn'); 
        
        if(userKeywords.length < 5) {
            AIZ.plugins.notify('warning', 'يرجى كتابة بضع كلمات عنك أولاً لنتمكن من صياغتها باحترافية.');
            return;
        }

        btn.find('.la-magic').hide();
        btn.find('.ai-typing-loader').show();
        btn.prop('disabled', true);

        saveBtn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> جاري الكتابة السحرية...');

        $.ajax({
            type: "POST",
            url: "{{ route('member.generate_ai_bio') }}",
            data: {
                _token: '{{ csrf_token() }}',
                keywords: userKeywords
            },
            success: function(response) {
                if(response.status === 'success') {
                    typeWriter(textarea, response.bio, 0);
                    AIZ.plugins.notify('success', 'يرجى الانتظار حتى ينتهي الذكاء الاصطناعي من الكتابة..');
                } else {
                    AIZ.plugins.notify('danger', 'حدث خطأ أثناء الاتصال بالذكاء الاصطناعي.');
                    saveBtn.prop('disabled', false).html('حفظ والانتقال للتالي <i class="las la-arrow-left mr-1"></i>');
                }
            },
            error: function() {
                AIZ.plugins.notify('danger', 'حدث خطأ في الاتصال. يرجى المحاولة لاحقاً.');
                saveBtn.prop('disabled', false).html('حفظ والانتقال للتالي <i class="las la-arrow-left mr-1"></i>');
            },
            complete: function() {
                btn.find('.la-magic').show();
                btn.find('.ai-typing-loader').hide();
                btn.prop('disabled', false);
            }
        });
    });

    function typeWriter(element, text, i) {
        if (i === 0) { 
            element.val(''); 
            element.text(''); 
        }
        
        if (i < text.length) {
            element.val(element.val() + text.charAt(i));
            element.text(element.val()); 
            i++;
            setTimeout(function() { typeWriter(element, text, i); }, 15);
        } else {
            calculateRealCompletion();
            $('#save-intro-btn').prop('disabled', false).html('حفظ والانتقال للتالي <i class="las la-arrow-left mr-1"></i>');
            AIZ.plugins.notify('success', 'اكتملت الكتابة! يمكنك الآن مراجعة النص والضغط على حفظ.');
        }
    }
</script>
@endsection