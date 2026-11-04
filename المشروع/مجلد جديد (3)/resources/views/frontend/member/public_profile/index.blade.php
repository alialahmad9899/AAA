{{-- 🛑 تغيير الوراثة لتكون من الـ App مباشرة لتجنب Sidebar الملف الشخصي وهوامشه 🛑 --}}
@extends('frontend.layouts.app')

@section('content')

<style>
    /* === التنسيقات الفاخرة لبروفايل العضو (Royal Theme) === */
    .royal-profile-wrapper { 
        background-color: var(--bg-body); 
        min-height: 100vh; 
        padding-bottom: 50px; 
        font-family: 'Cairo', sans-serif; 
    }
    
    /* الهيدر العلوي للبروفايل */
    .royal-profile-header { 
        background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.05); 
        padding: 50px 0 30px 0; 
        margin-bottom: 30px; 
    }
    
    /* البطاقات الداخلية */
    .royal-card { 
        background: #fff; 
        border-radius: 20px; 
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03); 
        border: 1px solid rgba(15, 23, 42, 0.05); 
        margin-bottom: 25px; 
        overflow: hidden; 
        transition: transform 0.3s ease;
    }
    .royal-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(212, 175, 55, 0.08); 
    }
    
    /* صورة البروفايل وإطارها الذهبي */
    .royal-avatar-container { 
        position: relative; 
        display: inline-block; 
        width: 180px; 
        height: 180px; 
        margin: 0 auto; 
        border-radius: 50%; 
        padding: 5px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--hov-primary) 100%);
        box-shadow: 0 10px 25px rgba(212, 175, 55, 0.3); 
    }
    .royal-avatar-container img {
        border: 4px solid #fff;
        border-radius: 50%;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* 🛑 تصليح الدائرة الرمادية (مصيدة الزوار/إخفاء الصورة) 🛑 */
    .royal-avatar-container .absolute-full {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6) !important;
        border-radius: 50%;
        backdrop-filter: blur(3px);
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        border: 4px solid #fff; /* تطابق الإطار الأبيض */
        z-index: 10;
        color: #fff;
    }

    /* أزرار الإجراءات الفخمة */
    .royal-action-btn { 
        border-radius: 12px; 
        font-weight: 800; 
        padding: 12px 20px; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        transition: all 0.3s ease; 
        border: none; 
        justify-content: center;
        font-size: 15px;
    }
    .royal-btn-primary { 
        background: linear-gradient(45deg, var(--secondary), #1e293b); 
        color: var(--primary) !important; 
        box-shadow: 0 5px 15px rgba(15, 23, 42, 0.2);
    }
    .royal-btn-primary:hover { 
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
    }
    .royal-btn-gold { 
        background: linear-gradient(45deg, var(--primary), var(--hov-primary)); 
        color: #fff !important; 
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
    }
    .royal-btn-gold:hover { 
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
    }
    .royal-btn-gray { 
        background-color: #f1f5f9; 
        color: var(--secondary) !important; 
        border: 1px solid #e2e8f0;
    }
    .royal-btn-gray:hover { 
        background-color: #e2e8f0; 
        color: var(--primary) !important;
    }

    /* قوائم المعلومات */
    .info-list { list-style: none; padding: 0; margin: 0; }
    .info-list li { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px dashed rgba(15, 23, 42, 0.05); }
    .info-list li:last-child { border-bottom: none; }
    .info-list li i { font-size: 22px; color: var(--primary); margin-left: 15px; width: 30px; text-align: center; }
    .info-list li strong { color: #64748b; width: 45%; font-weight: 700; text-align: right; margin-left: 10px; }
    .info-list li span { color: var(--secondary); font-weight: 800; }
    
    /* الأقسام القابلة للطي (Accordion) */
    .aiz-timeline-accordion .accordion-item { 
        border: 1px solid rgba(15, 23, 42, 0.05); 
        border-radius: 16px !important; 
        margin-bottom: 15px; 
        background: #fff; 
        overflow: hidden; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }
    .aiz-timeline-accordion .accordion-head { 
        padding: 15px 20px; 
        display: flex; 
        align-items: center; 
        background: #f8fafc; 
        transition: 0.3s;
    }
    .aiz-timeline-accordion .accordion-head:hover { background: #f1f5f9; }
    .aiz-timeline-accordion .accordion-body { padding: 25px; border-top: 1px solid rgba(15, 23, 42, 0.05); }
    
    /* تبويبات التصفح العلوية */
    .nav-tabs.aiz-nav-tabs { border-bottom: 2px solid #f1f5f9; }
    .nav-tabs.aiz-nav-tabs .nav-link { 
        color: #64748b; 
        font-weight: 800; 
        border: none; 
        padding: 15px 30px; 
        background: transparent !important; 
        font-size: 15px;
    }
    .nav-tabs.aiz-nav-tabs .nav-link.active { 
        color: var(--primary); 
        border-bottom: 3px solid var(--primary); 
    }

    @media (max-width: 991px) {
        .royal-profile-header { text-align: center; padding-top: 30px; }
        .flex-column-mobile { flex-direction: column !important; align-items: center !important; }
        .royal-avatar-container { width: 150px; height: 150px; }
        .nav-tabs.aiz-nav-tabs .nav-link { padding: 12px 15px; font-size: 14px; }
        .info-list li strong { width: 40%; }
    }
</style>

<div class="royal-profile-wrapper text-right" dir="rtl">
    {{-- هيدر البروفايل الملكي --}}
    <div class="royal-profile-header shadow-sm">
        <div class="container">
            <div class="row align-items-end mb-4 flex-column-mobile">
                <div class="col-lg-3 text-center">
                    @php
                        $auth_user = Auth::user();
                        $profile_pic_privacy = get_setting('profile_picture_privacy');
                        $photo_view_request = Auth::check() ? \App\Models\ViewProfilePicture::where('user_id', $user->id)->where('requested_by', $auth_user->id)->first() : null;
                        $avatar_image = $user->member?->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                        $profile_picture_show = show_profile_picture($user);
                    @endphp
                    
                    <div class="royal-avatar-container">
                        <img @if ($profile_picture_show) src="{{ uploaded_asset($user->photo) }}" @else src="{{ static_asset($avatar_image) }}" @endif
                             onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';">

                        {{-- 🛑 تم تنظيف كود الدائرة הرمادية للاعتماد على الـ CSS 🛑 --}}
                        @if (!$profile_picture_show)
                            <div class="absolute-full">
                                <i class="las la-lock la-3x"></i>
                            </div>
                        @endif
                    </div>
                    
                    @if (!Auth::check() || Auth::user()->id != $user->id)
                        @if ($user->photo != null && $user->photo_approved == 1 && $profile_pic_privacy == 'only_me' && ($photo_view_request == null || ($photo_view_request && $photo_view_request->status == 0)))
                            <button @if(!Auth::check()) onclick="showGuestModal()" @elseif ($photo_view_request == null) onclick="profile_pic_view_request({{ $user->id }})" @endif class="btn btn-sm btn-outline-primary mt-3 fw-800 rounded-pill">
                                @if ($photo_view_request == null)
                                    <i class="las la-unlock mr-1"></i> {{ translate('Send Photo View Request') }}
                                @elseif($photo_view_request && $photo_view_request->status == 0)
                                    <i class="las la-clock mr-1"></i> {{ translate('Photo View Request Sent') }}
                                @endif
                            </button>
                        @elseif($user->photo != null && $user->photo_approved == 1 && $profile_pic_privacy == 'premium_members' && (!Auth::check() || $auth_user->membership == 1))
                            <a @if(!Auth::check()) href="javascript:void(0);" onclick="showGuestModal()" @else href="{{ route('packages') }}" @endif class="btn btn-sm btn-outline-primary mt-3 fw-800 rounded-pill">
                                <i class="las la-gem mr-1"></i> {{ translate('Update Package to See Profile Picture') }}
                            </a>
                        @endif
                    @endif
                </div>

                <div class="col-lg-5 mb-4 text-center text-lg-right mt-3 mt-lg-0">
                    <h1 class="fw-900 mb-2" style="font-size: 2.2rem; color: var(--secondary);">{{ $user->first_name . ' ' . $user->last_name }}</h1>
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                        
                        
                        {{-- زر نسخ الرابط --}}
                        <button type="button" onclick="copyProfileLink('{{ route('member_profile', $user->id) }}')" class="btn btn-sm bg-white border shadow-sm px-3 py-1 fw-800" style="border-radius: 20px; color: var(--secondary);">
                            <i class="las la-copy text-primary fs-18 mr-1"></i> نسخ الرابط
                        </button>

                        @php
                            $profile_match = Auth::check() ? \App\Models\ProfileMatch::where('user_id', Auth::user()->id)->where('match_id', $user->id)->first() : null;
                        @endphp
                        @if (!empty($profile_match) && Auth::check() && optional(Auth::user()->member)->auto_profile_match == 1)
                            <span class="text-success fw-800 fs-14 ml-3" style="background: #ecfdf5; padding: 6px 12px; border-radius: 20px;">
                                <i class="las la-chart-bar mr-1"></i> {{ translate('Matched') }} - {{ $profile_match->match_percentage }}%
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    @if (!Auth::check() || Auth::user()->id != $user->id)
                        @php
                            $do_expressed_interest = Auth::check() ? \App\Models\ExpressInterest::where('user_id', $user->id)->where('interested_by', Auth::user()->id)->first() : null;
                            $received_expressed_interest = Auth::check() ? \App\Models\ExpressInterest::where('user_id', Auth::user()->id)->where('interested_by', $user->id)->first() : null;
                            
                            if (empty($do_expressed_interest) && empty($received_expressed_interest)) {
                                $interest_onclick = 1; $interest_text = translate('Express Interest'); $interest_icon = "lar la-heart";
                            } elseif (!empty($received_expressed_interest)) {
                                $interest_onclick = 'do_response'; $interest_text = ($received_expressed_interest->status == 0) ? translate('Response to Interest') : translate('Accepted'); $interest_icon = "las la-heart";
                            } else {
                                $interest_onclick = 0; $interest_text = ($do_expressed_interest->status == 0) ? translate('Request Sent') : translate('Connected'); $interest_icon = "las la-heart";
                            }
                        @endphp
                        
                        <div class="d-flex gap-2 justify-content-center justify-content-lg-start mb-3 mt-3 mt-lg-0">
                            <a href="javascript:void(0);" onclick="@if(Auth::check()) start_chat({{ $user->id }}) @else showGuestModal() @endif" class="royal-action-btn royal-btn-primary flex-grow-1 text-decoration-none">
                                <i class="las la-envelope fs-22"></i>
                                <span>{{ translate('Send Message') }}</span>
                            </a>

                            <a @if (!Auth::check()) onclick="showGuestModal()"
                               @elseif ($interest_onclick == 1) onclick="express_interest({{ $user->id }})"
                               @elseif($interest_onclick == 'do_response') href="{{ route('interest_requests') }}" @endif
                               class="royal-action-btn royal-btn-gold flex-grow-1 text-center justify-content-center c-pointer text-decoration-none">
                                <i class="{{ $interest_icon }} fs-22 text-white"></i>
                                <span id="interest_id_{{ $user->id }}">{{ $interest_text }}</span>
                            </a>
                        </div>

                        <div class="row gutters-5 justify-content-center justify-content-lg-start mb-2">
                            <div class="col-auto">
                                @php
                                    $shortlist = Auth::check() ? \App\Models\Shortlist::where('user_id', $user->id)->where('shortlisted_by', Auth::user()->id)->first() : null;
                                    $shortlist_onclick = empty($shortlist) ? 1 : 0;
                                    $shortlist_text = empty($shortlist) ? translate('Shortlist') : translate('Shortlisted');
                                    $shortlist_color = empty($shortlist) ? 'text-muted' : 'text-warning';
                                @endphp
                                <a href="javascript:void(0);" id="shortlist_a_id_{{ $user->id }}"
                                   @if (!Auth::check()) onclick="showGuestModal()"
                                   @elseif ($shortlist_onclick == 1) onclick="do_shortlist({{ $user->id }})"
                                   @else onclick="remove_shortlist({{ $user->id }})" @endif
                                   class="royal-action-btn royal-btn-gray text-decoration-none px-4">
                                    <i class="las la-star fs-22 {{ $shortlist_color }}"></i>
                                    <span id="shortlist_id_{{ $user->id }}">{{ $shortlist_text }}</span>
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="javascript:void(0);" onclick="@if(Auth::check()) ignore_member({{ $user->id }}) @else showGuestModal() @endif" class="royal-action-btn royal-btn-gray text-decoration-none px-4">
                                    <i class="las la-ban fs-22 opacity-60"></i>
                                    <span>{{ translate('Ignore') }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center justify-content-lg-start mt-3">
                            @php
                                $profile_reported = Auth::check() ? \App\Models\ReportedUser::where('user_id', $user->id)->where('reported_by', Auth::user()->id)->first() : null;
                                $report_onclick = empty($profile_reported) ? 1 : 0;
                                $report_text = empty($profile_reported) ? translate('Report Member') : translate('Reported');
                            @endphp
                            <button @if (!Auth::check()) onclick="showGuestModal()" @elseif ($report_onclick == 1) onclick="report_member({{ $user->id }})" @endif class="royal-action-btn w-100" style="background-color: #fff0f0; color: #ef4444; border: 1px solid #fecaca;">
                                <i class="las la-exclamation-circle fs-22"></i>
                                <span id="report_id_{{ $user->id }}">{{ $report_text }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="container p-0">
                <ul class="nav nav-tabs aiz-nav-tabs border-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#profile" role="tab">{{ translate('Detailed Profile') }}</a>
                    </li>
                    @if (get_setting('member_partner_expectation_section') == 'on')
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#preference" role="tab">{{ translate('Partner Preference') }}</a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#gallery" role="tab">{{ translate('Photo Gallery') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row d-flex flex-column-reverse flex-lg-row">
            {{-- الجانب الأيمن: نبذة سريعة وملفات مشابهة --}}
            <div class="col-xl-4 col-lg-5 order-2 order-lg-1">
                <div class="royal-card p-4">
                    <h5 class="fw-900 mb-3 border-bottom pb-3" style="color: var(--secondary);">{{ translate('Intro') }}</h5>
                    <ul class="info-list">
                        <li>
                            <i class="las la-birthday-cake"></i>
                            <strong>{{ translate('Age') }}:</strong>
                            <span>{{ !empty($user->member?->birthday) ? \Carbon\Carbon::parse($user->member->birthday)->age : translate('N/A') }} {{ translate('yrs') }}</span>
                        </li>
                        <li>
                            <i class="las la-heart"></i>
                            <strong>{{ translate('Status') }}:</strong>
                            <span>{{ $user->member?->marital_status?->name ?? translate('N/A') }}</span>
                        </li>
                        <li>
                            <i class="las la-pray"></i>
                            <strong>{{ translate('Religion') }}:</strong>
                            <span>{{ $user->spiritual_backgrounds?->religion?->name ?? translate('N/A') }}</span>
                        </li>
                        @php
                            $present_address = \App\Models\Address::where('user_id', $user->id)->where('type', 'present')->first();
                        @endphp
                        @if (!empty($present_address?->country?->name))
                        <li>
                            <i class="las la-map-marker-alt"></i>
                            <strong>{{ translate('Lives in') }}:</strong>
                            <span>{{ $present_address?->country?->name ?? translate('N/A') }}</span>
                        </li>
                        @endif
                    </ul>
                </div>

                {{-- الملفات المشابهة --}}
                @if (Auth::check() && optional(Auth::user()->member)->auto_profile_match == 1 && isset($similar_profiles) && count($similar_profiles) > 0)
                    <div class="royal-card p-4 text-center">
                        <h5 class="fw-900 mb-4 border-bottom pb-3" style="color: var(--secondary);">{{ translate('Similar Profiles') }}</h5>
                        <div class="row gutters-10">
                            @foreach ($similar_profiles->shuffle()->take(6) as $similar_profile)
                                @if ($similar_profile->user != null)
                                    <div class="col-4 mb-3 text-center">
                                        <a href="{{ route('member_profile', $similar_profile->match_id) }}" class="text-reset d-block text-center hov-column">
                                            @php
                                                $sim_avatar = $similar_profile->user->member?->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                            @endphp
                                            <img src="{{ uploaded_asset($similar_profile->user->photo) }}" 
                                                 onerror="this.onerror=null;this.src='{{ static_asset($sim_avatar) }}';"
                                                 class="img-fluid rounded-circle shadow-sm mb-2 border" style="aspect-ratio: 1/1; object-fit: cover; border-color: var(--primary) !important;">
                                            <div class="small fw-800 text-truncate" style="color: var(--secondary);">{{ $similar_profile->user->first_name }}</div>
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- الجانب الأيسر: الأقسام المفصلة --}}
            <div class="col-xl-8 col-lg-7 order-1 order-lg-2">
                <div class="tab-content">
                    
                    {{-- تبويب الملف الشخصي التفصيلي --}}
                    <div class="tab-pane fade show active" id="profile">
                        <div class="royal-card mb-4 p-4 text-right">
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <i class="las la-quote-right fs-26 ml-2" style="color: var(--primary);"></i>
                                <h5 class="fw-900 mb-0" style="color: var(--secondary);">نبذة عن {{ $user->first_name }}</h5>
                            </div>
                            <p class="lh-2 fs-15 mb-0 p-3 rounded" style="background: rgba(212, 175, 55, 0.05); color: var(--secondary); border-right: 4px solid var(--primary); font-weight: 600;">
                                {{ $user->member?->introduction ?? translate('No introduction provided.') }}
                            </p>
                        </div>

                        <div class="accordion aiz-timeline-accordion" id="profile-accordion">
                            
                            {{-- المعلومات الأساسية --}}
                            <div class="accordion-item">
                                <div class="accordion-head c-pointer" data-toggle="collapse" data-target="#basic-info">
                                    <span class="size-45px border rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm ml-3">
                                        <i class="las la-id-card fs-24" style="color: var(--primary);"></i>
                                    </span>
                                    <h6 class="mb-0 fw-900" style="color: var(--secondary);">{{ translate('Basic Information') }}</h6>
                                </div>
                                <div id="basic-info" class="collapse show accordion-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="info-list shadow-none border-0">
                                                <li><strong>{{ translate('First Name') }}:</strong> <span>{{ $user->first_name ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Gender') }}:</strong> <span>{{ optional($user->member)->gender == 1 ? translate('Male') : translate('Female') }}</span></li>
                                                <li><strong>{{ translate('Age') }}:</strong> <span>{{ !empty($user->member?->birthday) ? \Carbon\Carbon::parse($user->member->birthday)->age : translate('N/A') }} {{ translate('yrs') }}</span></li>
                                                <li><strong>{{ translate('Religion') }}:</strong> <span>{{ $user->spiritual_backgrounds?->religion?->name ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6 border-right">
                                            <ul class="info-list shadow-none border-0">
                                                <li><strong>{{ translate('Last Name') }}:</strong> <span>{{ $user->last_name ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Date of Birth') }}:</strong> <span>{{ !empty($user->member?->birthday) ? date('d/m/Y', strtotime($user->member->birthday)) : translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Marital Status') }}:</strong> <span>{{ $user->member?->marital_status?->name ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('No. of Children') }}:</strong> <span>{{ $user->member?->children ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- العنوان الحالي --}}
                            @if (get_setting('member_present_address_section') == 'on')
                            <div class="accordion-item">
                                <div class="accordion-head c-pointer collapsed" data-toggle="collapse" data-target="#present-address-tab">
                                    <span class="size-45px border rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm ml-3">
                                        <i class="las la-map-marker fs-24" style="color: var(--primary);"></i>
                                    </span>
                                    <h6 class="mb-0 fw-900" style="color: var(--secondary);">{{ translate('Present Address') }}</h6>
                                </div>
                                <div id="present-address-tab" class="collapse accordion-body">
                                    @php $present_address = \App\Models\Address::where('user_id', $user->id)->where('type', 'present')->first(); @endphp
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="info-list shadow-none border-0">
                                                <li><strong>{{ translate('Country') }}:</strong> <span>{{ $present_address?->country?->name ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('City') }}:</strong> <span>{{ $present_address?->city?->name ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6 border-right">
                                            <ul class="info-list shadow-none border-0">
                                                <li><strong>{{ translate('State') }}:</strong> <span>{{ $present_address?->state?->name ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- معلومات الاتصال (محمية) --}}
                            <div class="accordion-item shadow-none border border-width-2" style="border-color: var(--primary) !important; background: rgba(212, 175, 55, 0.02);">
                                <div class="accordion-head c-pointer" data-toggle="collapse" data-target="#contact-sec" style="background: transparent;">
                                    <span class="size-45px rounded-circle d-flex align-items-center justify-content-center ml-3 shadow-sm" style="background: linear-gradient(135deg, var(--primary), var(--hov-primary)); color: #fff;">
                                        <i class="las la-phone-volume fs-24"></i>
                                    </span>
                                    <h6 class="mb-0 fw-900" style="color: var(--secondary);">{{ translate('Contact Details') }}</h6>
                                </div>
                                <div id="contact-sec" class="collapse accordion-body bg-white">
                                    @php $view_contact = Auth::check() ? \App\Models\ViewContact::where('user_id', $user->id)->where('viewed_by', Auth::user()->id)->first() : null; @endphp
                                    @if (!empty($view_contact) || (Auth::check() && Auth::user()->id == $user->id))
                                        <ul class="info-list">
                                            <li><i class="las la-phone"></i> <strong>{{ translate('Phone Number') }}:</strong> <span style="direction: ltr; display: inline-block;">{{ $user->phone ?? translate('N/A') }}</span></li>
                                            <li><i class="las la-envelope"></i> <strong>{{ translate('Email ID') }}:</strong> <span>{{ $user->email ?? translate('N/A') }}</span></li>
                                        </ul>
                                    @else
                                        <div class="text-center p-4">
                                            <i class="las la-lock la-3x opacity-40 mb-3 d-block" style="color: var(--secondary);"></i>
                                            <h6 class="fw-900 mb-3" style="color: var(--secondary);">{{ translate('Contact Information is Hidden') }}</h6>
                                            <button onclick="@if(Auth::check()) view_contact({{ $user->id }}) @else showGuestModal() @endif" class="royal-action-btn royal-btn-primary rounded-pill px-5 shadow-sm mx-auto">
                                                <i class="las la-key"></i> {{ translate('Unlock Contact Info') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- التعليم --}}
                            @if (get_setting('member_education_section') == 'on')
                            <div class="accordion-item pb-1">
                                <div class="accordion-head c-pointer collapsed" data-toggle="collapse" data-target="#education-sec">
                                    <span class="size-45px border rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm ml-3">
                                        <i class="las la-graduation-cap fs-24" style="color: var(--primary);"></i>
                                    </span>
                                    <h6 class="mb-0 fw-900" style="color: var(--secondary);">المؤهلات التعليمية</h6>
                                </div>
                                <div id="education-sec" class="collapse accordion-body">
                                    @php $educations = \App\Models\Education::where('user_id', $user->id)->orderBy('is_highest_degree', 'desc')->get() ?? []; @endphp
                                    @forelse ($educations as $edu)
                                        <div class="p-3 bg-light rounded mb-2 border-right text-right" style="border-color: var(--primary) !important; border-width: 3px !important;">
                                            <div class="fw-800 fs-16" style="color: var(--secondary);">{{ $edu->degree }}</div>
                                            <div class="text-muted fs-14 fw-600">{{ $edu->institution }}</div>
                                        </div>
                                    @empty
                                        <div class="text-muted small p-3 text-center fw-600">لا توجد بيانات تعليمية مضافة</div>
                                    @endforelse
                                </div>
                            </div>
                            @endif

                            {{-- المهنة --}}
                            @if (get_setting('member_career_section') == 'on')
                            <div class="accordion-item pb-1">
                                <div class="accordion-head c-pointer collapsed" data-toggle="collapse" data-target="#career-sec">
                                    <span class="size-45px border rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm ml-3">
                                        <i class="las la-briefcase fs-24" style="color: var(--primary);"></i>
                                    </span>
                                    <h6 class="mb-0 fw-900" style="color: var(--secondary);">الحياة المهنية</h6>
                                </div>
                                <div id="career-sec" class="collapse accordion-body">
                                    @php $careers = \App\Models\Career::where('user_id', $user->id)->orderBy('present', 'desc')->get() ?? []; @endphp
                                    @forelse ($careers as $career)
                                        <div class="p-3 bg-light rounded mb-2 border-right text-right" style="border-color: var(--primary) !important; border-width: 3px !important;">
                                            <div class="fw-800 fs-16" style="color: var(--secondary);">{{ $career->designation }}</div>
                                            <div class="text-muted fs-14 fw-600">{{ $career->company }}</div>
                                        </div>
                                    @empty
                                        <div class="text-muted small p-3 text-center fw-600">لا توجد بيانات مهنية مضافة</div>
                                    @endforelse
                                </div>
                            </div>
                            @endif

                            {{-- المواصفات الجسدية --}}
                            @if (get_setting('member_physical_attributes_section') == 'on')
                            <div class="accordion-item pb-1">
                                <div class="accordion-head c-pointer collapsed" data-toggle="collapse" data-target="#physical-sec">
                                    <span class="size-45px border rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm ml-3">
                                        <i class="las la-user-alt fs-24" style="color: var(--primary);"></i>
                                    </span>
                                    <h6 class="mb-0 fw-900" style="color: var(--secondary);">المواصفات الجسدية</h6>
                                </div>
                                <div id="physical-sec" class="collapse accordion-body">
                                    <div class="row text-right">
                                        <div class="col-md-6">
                                            <ul class="info-list shadow-none border-0 p-0">
                                                <li><strong>{{ translate('Height') }}:</strong> <span>{{ $user->physical_attributes?->height ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Eye Color') }}:</strong> <span>{{ $user->physical_attributes?->eye_color ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Complexion') }}:</strong> <span>{{ $user->physical_attributes?->complexion ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6 border-right">
                                            <ul class="info-list shadow-none border-0 p-0">
                                                <li><strong>{{ translate('Weight') }}:</strong> <span>{{ $user->physical_attributes?->weight ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Hair Color') }}:</strong> <span>{{ $user->physical_attributes?->hair_color ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Body Type') }}:</strong> <span>{{ $user->physical_attributes?->body_type ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- الهوايات --}}
                            @if (get_setting('member_hobbies_and_interests_section') == 'on')
                            <div class="accordion-item pb-1">
                                <div class="accordion-head c-pointer collapsed" data-toggle="collapse" data-target="#hobbies-sec">
                                    <span class="size-45px border rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm ml-3">
                                        <i class="las la-icons fs-24" style="color: var(--primary);"></i>
                                    </span>
                                    <h6 class="mr-3 mb-0 fw-900" style="color: var(--secondary);">{{ translate('Hobbies & Interests') }}</h6>
                                </div>
                                <div id="hobbies-sec" class="collapse accordion-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="info-list shadow-none border-0">
                                                <li><strong>{{ translate('Hobbies') }}:</strong> <span>{{ $user->hobbies?->hobbies ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Music') }}:</strong> <span>{{ $user->hobbies?->music ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Movies') }}:</strong> <span>{{ $user->hobbies?->movies ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6 border-right">
                                            <ul class="info-list shadow-none border-0">
                                                <li><strong>{{ translate('Interests') }}:</strong> <span>{{ $user->hobbies?->interests ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Sports') }}:</strong> <span>{{ $user->hobbies?->sports ?? translate('N/A') }}</span></li>
                                                <li><strong>{{ translate('Books') }}:</strong> <span>{{ $user->hobbies?->books ?? translate('N/A') }}</span></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>
                    
                    {{-- تبويب توقعات الشريك --}}
                    @if (get_setting('member_partner_expectation_section') == 'on')
                    <div class="tab-pane fade" id="preference">
                        <div class="royal-card p-4">
                            <h5 class="fw-900 mb-4 border-bottom pb-3" style="color: var(--secondary);">{{ translate('Partner Preference') }}</h5>
                            @if (!empty($user->partner_expectations))
                                <div class="row text-right">
                                    <div class="col-md-6">
                                        <ul class="info-list shadow-none border-0 p-0">
                                            <li><strong>{{ translate('General') }}:</strong> <span>{{ $user->partner_expectations?->general ?? translate('N/A') }}</span></li>
                                            <li><strong>{{ translate('Min Height') }}:</strong> <span>{{ $user->partner_expectations?->height ?? translate('N/A') }}</span></li>
                                            <li><strong>{{ translate('Weight') }}:</strong> <span>{{ $user->partner_expectations?->weight ?? translate('N/A') }}</span></li>
                                            <li><strong>{{ translate('Marital Status') }}:</strong> <span>{{ $user->partner_expectations?->marital_status?->name ?? translate('N/A') }}</span></li>
                                            <li><strong>{{ translate('Religion') }}:</strong> <span>{{ $user->partner_expectations?->religion?->name ?? translate('N/A') }}</span></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 border-right">
                                        <ul class="info-list shadow-none border-0 p-0">
                                            <li><strong>{{ translate('Education') }}:</strong> <span>{{ $user->partner_expectations?->education ?? translate('N/A') }}</span></li>
                                            <li><strong>{{ translate('Profession') }}:</strong> <span>{{ $user->partner_expectations?->profession ?? translate('N/A') }}</span></li>
                                            <li><strong>{{ translate('Preferred Country') }}:</strong> <span>
                                                @php $pref_country = \App\Models\Country::find($user->partner_expectations?->preferred_country_id); @endphp
                                                {{ $pref_country?->name ?? translate('Any') }}
                                            </span></li>
                                            <li><strong>{{ translate('Complexion') }}:</strong> <span>{{ $user->partner_expectations?->complexion ?? translate('N/A') }}</span></li>
                                        </ul>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5 fw-700 text-muted">{{ translate('No partner expectations specified.') }}</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- تبويب معرض الصور --}}
                    <div class="tab-pane fade" id="gallery">
                        <div class="royal-card p-4">
                            <h5 class="fw-900 mb-4 border-bottom pb-3 text-center" style="color: var(--secondary);">{{ translate('Photo Gallery') }}</h5>
                            @php
                                $gallery_image_show = true;
                                $gallery_image_privacy = get_setting('gallery_image_privacy');
                                $gallery_view_request = Auth::check() ? \App\Models\ViewGalleryImage::where('user_id', $user->id)->where('requested_by', $auth_user->id)->first() : null;
                                
                                if ($gallery_image_privacy == 'only_me' && (!Auth::check() || Auth::user()->id != $user->id)) {
                                    if ($gallery_view_request != null && $gallery_view_request->status == 1) {
                                        $gallery_image_show = true;
                                    } else {
                                        $gallery_image_show = false;
                                    }
                                } elseif($gallery_image_privacy == 'premium_members' && (!Auth::check() || Auth::user()->id != $user->id)) {
                                    if (!Auth::check() || $auth_user->membership != 1) {
                                        $gallery_image_show = false;
                                    }
                                }
                                $gallery_images = \App\Models\GalleryImage::where('user_id', $user->id)->latest()->get();
                            @endphp

                            <div class="row gutters-10">
                                @forelse ($gallery_images as $img)
                                    <div class="col-md-4 col-6 mb-3">
                                        @if($gallery_image_show)
                                            <div class="rounded-lg overflow-hidden border shadow-sm c-pointer hov-overlay" data-toggle="modal" data-target="#galleryImageModal" data-image="{{ uploaded_asset($img->image) }}">
                                                <img src="{{ uploaded_asset($img->image) }}" class="img-fit h-200px w-100">
                                                <div class="overlay d-flex align-items-center justify-content-center" style="background: rgba(15, 23, 42, 0.5);">
                                                    <i class="las la-search-plus text-white la-3x"></i>
                                                </div>
                                            </div>
                                        @else
                                            <div class="rounded-lg d-flex align-items-center justify-content-center border shadow-sm" style="height:200px; background: rgba(15, 23, 42, 0.05);">
                                                <i class="las la-lock la-3x" style="color: var(--secondary); opacity: 0.5;"></i>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="col-12 py-5 text-center text-muted fw-700">
                                        <i class="las la-images la-4x opacity-30 mb-3 d-block" style="color: var(--secondary);"></i>
                                        <p>{{ translate('No photos shared yet.') }}</p>
                                    </div>
                                @endforelse
                            </div>

                            @if (count($gallery_images) > 0 && !$gallery_image_show)
                                <div class="text-center mt-4 border-top pt-4">
                                    @if($gallery_image_privacy == 'only_me')
                                        <button @if(!Auth::check()) onclick="showGuestModal()" @elseif ($gallery_view_request == null) onclick="gallery_image_view_request({{ $user->id }})" @endif class="royal-action-btn royal-btn-primary rounded-pill px-5 shadow-sm mx-auto">
                                            @if ($gallery_view_request == null)
                                                <i class="las la-unlock"></i> {{ translate('Send Gallery Photo View Request') }}
                                            @elseif($gallery_view_request->status == 0)
                                                <i class="las la-clock"></i> {{ translate('Gallery Photo View Request Sent') }}
                                            @endif
                                        </button>
                                    @else
                                        <a @if(!Auth::check()) href="javascript:void(0);" onclick="showGuestModal()" @else href="{{ route('packages') }}" @endif class="royal-action-btn royal-btn-gold rounded-pill px-5 shadow-sm mx-auto">
                                            <i class="las la-gem"></i> {{ translate('Update Package to View Gallery') }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- مودال عرض الصور المحدث --}}
<div class="modal fade" id="galleryImageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content position-relative bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <button type="button" class="btn position-absolute rounded-circle shadow-lg" id="prevImageBtn" style="top:50%; left:10px; transform:translateY(-50%); z-index:10; background: rgba(15, 23, 42, 0.8); color: #fff; width: 45px; height: 45px;">
                    <i class="las la-arrow-left fs-20"></i>
                </button>
                <img src="" id="galleryImageModalSrc" class="img-fluid rounded shadow-lg border" alt="Gallery Image" style="max-height: 85vh; border-color: var(--primary) !important; border-width: 3px !important;">
                <button type="button" class="btn position-absolute rounded-circle shadow-lg" id="nextImageBtn" style="top:50%; right:10px; transform:translateY(-50%); z-index:10; background: rgba(15, 23, 42, 0.8); color: #fff; width: 45px; height: 45px;">
                    <i class="las la-arrow-right fs-20"></i>
                </button>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn rounded-pill px-4 fw-800" data-dismiss="modal" style="background: rgba(255, 255, 255, 0.9); color: var(--secondary);">
                    <i class="las la-times mr-1"></i>{{ translate('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- النافذة المنبثقة للزوار (المصيدة الزجاجية الفخمة) --}}
<div class="modal fade" id="guestLoginModal" tabindex="-1" role="dialog" aria-labelledby="publicProfileAppInfoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 22px; overflow: hidden;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center px-4 px-md-5 pb-5 pt-2" dir="rtl">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width: 76px; height: 76px; background: rgba(212, 175, 55, .14); border: 1px solid rgba(212, 175, 55, .35);">
                    <i class="las la-mobile-alt" style="font-size: 34px; color: var(--primary);"></i>
                </div>
                <h4 id="publicProfileAppInfoTitle" class="fw-900 mb-3" style="color: var(--secondary);">التواصل عبر تطبيق وصال</h4>
                <p class="text-muted fs-15 mb-4 fw-600" style="line-height: 1.9;">تصفّح المعلومات العامة المتاحة هنا، ولإرسال طلبات التواصل واستخدام الميزات التفاعلية، تابع من خلال تطبيق وصال. ستُضاف روابط التحميل الرسمية إلى الموقع عند اعتمادها.</p>
                <button type="button" class="btn rounded-pill px-5 py-2 fw-800" data-dismiss="modal" style="background: var(--secondary); color: #fff;">حسناً</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.package_update_alert_modal')
    @include('modals.confirm_modal')
    
    <div class="modal fade ignore_member_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title h6 fw-900 text-dark">{{ translate('Ignore Member!') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pt-4 pb-5">
                    <i class="las la-eye-slash la-4x text-muted mb-3 d-block"></i>
                    <p class="fw-700 fs-15 text-dark">{{ translate('Are you sure that you want to ignore this member?') }}</p>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-800" data-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 fw-800 shadow-sm" id="ignore_button">{{ translate('Ignore') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade report_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title h6 fw-900 text-danger"><i class="las la-exclamation-triangle mr-1"></i>{{ translate('Report Member') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 text-right" dir="rtl">
                    <form action="{{ route('reportusers.store') }}" id="report-modal-form" method="POST">
                        @csrf
                        <input type="hidden" name="member_id" id="member_id" value="">
                        <div class="form-group">
                            <label class="fw-800 text-dark mb-2">{{ translate('Reason for reporting') }} <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="4" class="form-control" style="border-radius: 12px; background: #f8fafc;" placeholder="{{ translate('Describe the issue...') }}" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-800" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-800 shadow-sm" onclick="submitReport()">{{ translate('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script type="text/javascript">
    // تم تحصين جميع متغيرات الجافاسكريبت لتعمل بسلاسة حتى مع الزوار
    var is_guest = {{ Auth::check() ? 'false' : 'true' }};
    var package_validity = {{ Auth::check() && package_validity(Auth::user()->id) ? 'true' : 'false' }};
    var user_id = {{ Auth::check() ? Auth::user()->id : 'null' }};
    var remaining_profile_image_view = {{ Auth::check() ? get_remaining_package_value(Auth::user()->id, 'remaining_profile_image_view') : 0 }};
    var remaining_gallery_image_view = {{ Auth::check() ? get_remaining_package_value(Auth::user()->id, 'remaining_gallery_image_view') : 0 }};

    // دالة إظهار نافذة الزوار
    function showGuestModal() {
        $('#guestLoginModal').modal('show');
    }

    function start_chat(id) {
        if (is_guest) { showGuestModal(); return; }
        $.ajax({
            type: "POST",
            url: "{{ route('chat.store') }}",
            data: {
                _token: '{{ csrf_token() }}',
                id: id
            },
            success: function(data) {
                if (data.status === 'success') {
                    window.location.href = "{{ route('all.messages') }}?member_id=" + id;
                } else if (data.status === 'error') {
                    AIZ.plugins.notify('warning', data.message);
                    $('.package_update_alert_modal').modal('show');
                }
            },
            error: function(xhr) {
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        });
    }

    function express_interest(id) {
        if (is_guest) { showGuestModal(); return; }
        $.post('{{ route('user.remaining_package_value') }}', { _token: '{{ csrf_token() }}', id: user_id, colmn_name: 'remaining_interest' }, function(data) {
            if (!package_validity || data < 1) { 
                $('.package_update_alert_modal').modal('show'); 
            } else {
                $('.confirm_modal').modal('show');
                $("#confirm_modal_title").html("{{ translate('Confirm Interest!') }}");
                $("#confirm_modal_content").html("<div class='text-center p-3'><i class='las la-heart la-4x text-danger mb-3'></i><p class='h6 fw-800 text-dark'>{{ translate('Send interest to this member?') }}</p></div>");
                $("#confirm_button").attr("onclick", "do_express_interest(" + id + ")").addClass('rounded-pill px-4 fw-800');
            }
        });
    }

    function do_express_interest(id) {
        $('.confirm_modal').modal('hide');
        $("#interest_id_" + id).html("{{ translate('Processing...') }}");
        $.post('{{ route('express-interest.store') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data) { 
                AIZ.plugins.notify('success', '{{ translate('Interest expressed successfully') }}'); 
                location.reload(); 
            }
        });
    }

    function view_contact(id) {
        if (is_guest) { showGuestModal(); return; }
        $.post('{{ route('user.remaining_package_value') }}', { _token: '{{ csrf_token() }}', id: user_id, colmn_name: 'remaining_contact_view' }, function(data) {
            if (!package_validity || data < 1) { 
                $('.package_update_alert_modal').modal('show'); 
            } else {
                $('.confirm_modal').modal('show');
                $("#confirm_modal_title").html("{{ translate('View Contact') }}");
                $("#confirm_modal_content").html("<div class='text-center p-3'><i class='las la-phone-volume la-4x mb-3' style='color: var(--primary);'></i><p class='h6 fw-800 text-dark'>{{ translate('Unlock contact info for 1 point?') }}</p></div>");
                $("#confirm_button").attr("onclick", "do_contact_view(" + id + ")").addClass('rounded-pill px-4 fw-800');
            }
        });
    }

    function do_contact_view(id) {
        $('.confirm_modal').modal('hide');
        $.post('{{ route('view_contacts.store') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) { 
            if (data) { location.reload(); } 
        });
    }

    function do_shortlist(id) {
        if (is_guest) { showGuestModal(); return; }
        $("#shortlist_a_id_" + id).removeAttr("onclick");
        $("#shortlist_id_" + id).html("{{ translate('Shortlisting') }}..");
        $.post('{{ route('member.add_to_shortlist') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data == 1) {
                $("#shortlist_id_" + id).html("{{ translate('Shortlisted') }}");
                $("#shortlist_a_id_" + id).attr("onclick", "remove_shortlist(" + id + ")").find('i').addClass('text-warning');
                AIZ.plugins.notify('success', '{{ translate('You Have Shortlisted This Member.') }}');
            } else {
                $("#shortlist_id_" + id).html("{{ translate('Shortlist') }}");
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        });
    }

    function remove_shortlist(id) {
        if (is_guest) { showGuestModal(); return; }
        $("#shortlist_a_id_" + id).removeAttr("onclick");
        $("#shortlist_id_" + id).html("{{ translate('Removing') }}..");
        $.post('{{ route('member.remove_from_shortlist') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data == 1) {
                $("#shortlist_id_" + id).html("{{ translate('Shortlist') }}");
                $("#shortlist_a_id_" + id).attr("onclick", "do_shortlist(" + id + ")").find('i').removeClass('text-warning');
                AIZ.plugins.notify('success', '{{ translate('You Have Removed This Member From Your Shortlist.') }}');
            } else {
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        });
    }

    function ignore_member(id) {
        if (is_guest) { showGuestModal(); return; }
        $('.ignore_member_modal').modal('show');
        $("#ignore_button").attr("onclick", "do_ignore(" + id + ")");
    }

    function do_ignore(id) {
        $.post('{{ route('member.add_to_ignore_list') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data == 1) {
                $('.ignore_member_modal').modal('toggle');
                AIZ.plugins.notify('success', '{{ translate('You Have Ignored This Member.') }}');
                window.location.href = "{{ route('member.listing') }}";
            } else {
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        });
    }

    function report_member(id) { 
        if (is_guest) { showGuestModal(); return; }
        $('.report_modal').modal('show'); $('#member_id').val(id); 
    }
    
    function submitReport() { if($('#report-modal-form textarea').val().length > 0) { $('#report-modal-form').submit(); } }

    function profile_pic_view_request(id) {
        if (is_guest) { showGuestModal(); return; }
        if (!package_validity || remaining_profile_image_view < 1) {
            $('.package_update_alert_modal').modal('show');
        } else {
            $('.confirm_modal').modal('show');
            $("#confirm_modal_title").html("{{ translate('Profile Picture View') }}");
            $("#confirm_modal_content").html("<div class='text-center p-3'><i class='las la-image la-4x mb-3' style='color: var(--primary);'></i><p class='h6 fw-800 text-dark'>{{ translate('Unlock Profile Picture?') }}</p></div>");
            $("#confirm_button").attr("onclick", "send_profile_pic_view_request(" + id + ")").addClass('rounded-pill px-4 fw-800');
        }
    }

    function send_profile_pic_view_request(id) {
        $('.confirm_modal').modal('toggle');
        $.post('{{ route('profile-picture-view-request.store') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data == 1) {
                AIZ.plugins.notify('success', '{{ translate('Profile Picture view request sent successfully.') }}');
                location.reload();
            } else {
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        });
    }

    function gallery_image_view_request(id) {
        if (is_guest) { showGuestModal(); return; }
        if (!package_validity || remaining_gallery_image_view < 1) {
            $('.package_update_alert_modal').modal('show');
        } else {
            $('.confirm_modal').modal('show');
            $("#confirm_modal_title").html("{{ translate('Gallery Image View') }}");
            $("#confirm_modal_content").html("<div class='text-center p-3'><i class='las la-images la-4x mb-3' style='color: var(--primary);'></i><p class='h6 fw-800 text-dark'>{{ translate('Unlock Gallery Images?') }}</p></div>");
            $("#confirm_button").attr("onclick", "send_gallery_image_view_request(" + id + ")").addClass('rounded-pill px-4 fw-800');
        }
    }

    function send_gallery_image_view_request(id) {
        $('.confirm_modal').modal('hide');
        $.post('{{ route('gallery-image-view-request.store') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
            if (data == 1) {
                AIZ.plugins.notify('success', '{{ translate('Gallery image view request sent successfully.') }}');
                location.reload();
            } else {
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        });
    }

    // دالة نسخ رابط البروفايل
    function copyProfileLink(url) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(url).select();
        try {
            document.execCommand("copy");
            AIZ.plugins.notify('success', 'تم نسخ رابط الملف الشخصي بنجاح!');
        } catch (err) {
            AIZ.plugins.notify('danger', 'حدث خطأ أثناء نسخ الرابط.');
        }
        $temp.remove();
    }

    // Gallery Slider Script
    $(document).ready(function () {
        var galleryImages = [];
        var currentIndex = 0;
    
        $('.hov-overlay[data-image]').each(function () {
            galleryImages.push($(this).data('image'));
        });
    
        $('#galleryImageModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var imageSrc = button.data('image');
            currentIndex = galleryImages.indexOf(imageSrc);
            updateModalImage();
        });
    
        function updateModalImage() {
            $('#galleryImageModalSrc').attr('src', galleryImages[currentIndex]);
        }
    
        $('#nextImageBtn').click(function () {
            currentIndex = (currentIndex + 1) % galleryImages.length;
            updateModalImage();
        });
    
        $('#prevImageBtn').click(function () {
            currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
            updateModalImage();
        });
    
        $('#galleryImageModal').on('hidden.bs.modal', function () {
            $('#galleryImageModalSrc').attr('src', '');
        });
    });
</script>
@endsection