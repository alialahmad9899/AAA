<style>
    /* التنسيقات الفخمة لبطاقات الصفحة الرئيسية */
    .main-luxury-card {
        height: 350px !important; /* الالتزام بالمقاس المطلوب */
        border: 1px solid rgba(0,0,0,0.05) !important;
        transition: all 0.4s ease-in-out;
        background: #fff;
        border-radius: 15px !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .main-luxury-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.12) !important;
    }

    .main-luxury-card .img-fit {
        transition: all 0.6s ease;
        height: 350px !important;
    }

    .main-luxury-card:hover .img-fit {
        transform: scale(1.08);
    }

    /* شريط الاسم الزجاجي الفاخر */
    .glass-info-bar {
        position: absolute;
        bottom: 12px;
        left: 12px;
        right: 12px;
        background: rgba(255, 255, 255, 0.82) !important;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 12px;
        padding: 12px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        z-index: 2;
    }

    /* نقطة المتصل المضيئة */
    .status-glow {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 11px;
        height: 11px;
        background-color: #2ecc71;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 10px rgba(46, 204, 113, 0.8);
        z-index: 3;
    }

    /* تظليل سفلي ناعم خلف النص */
    .bottom-gradient {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 40%;
        background: linear-gradient(to top, rgba(0,0,0,0.2), transparent);
        z-index: 1;
    }

    .private-photo-blur {
        background: rgba(0, 0, 0, 0.4) !important;
        backdrop-filter: blur(12px);
        color: #fff;
        z-index: 2;
    }
</style>

<div class="main-luxury-card position-relative overflow-hidden">
    @if(Cache::has('user-is-online-' . $member->id))
        <span class="status-glow" title="متصل الآن"></span>
    @endif

	<a
		@if(!Auth::check())
			onclick="loginModal()"
		@elseif(get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1)
            href="javascript:void(0);" onclick="package_update_alert()"
        @else
            href="{{ route('member_profile', $member->id) }}"
        @endif
		class="d-block text-reset c-pointer h-100"
	>
		@php
			$avatar_image = (optional($member->member)->gender == 1) ? 'assets/img/avatar-place.png' : ((optional($member->member)->gender == 2) ? 'assets/img/female-avatar-place.png' : null);
			$profile_picture_show = show_profile_picture($member);
		@endphp

        <img
				@if($profile_picture_show)
					src="{{ uploaded_asset($member->photo) }}"
				@else
					src="{{ static_asset($avatar_image) }}"
				@endif
				onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';"
				class="img-fit mw-100 h-100"
		>
        
        <div class="bottom-gradient"></div>

		@if(!$profile_picture_show)
			<div class="absolute-full d-flex flex-column justify-content-center align-items-center private-photo-blur">
                <i class="las la-lock la-3x mb-2" style="opacity: 0.8;"></i>
                <span class="fs-11 fw-600 text-uppercase" style="letter-spacing: 2px;">صورة خاصة</span>
            </div>
		@endif

        <div class="glass-info-bar">
			<div class="text-center">
				<div class="text-dark fw-700 fs-15 mb-0" style="letter-spacing: -0.3px;">{{ $member->first_name}}</div>
                <div class="d-flex align-items-center justify-content-center mt-1" style="opacity: 0.8;">
                    <span class="fs-10 text-muted">{{ translate('ID: ') }}</span>
                    <span class="ml-1 text-primary fw-600 fs-11">{{ $member->code }}</span>
                </div>
			</div>
		</div>
	</a>
</div>