@php
    $memberGender = optional($profile->member)->gender;
    $avatarPath = $memberGender == 2 ? 'assets/img/female-avatar-place.png' : 'assets/img/avatar-place.png';
    $canShowPhoto = !empty($profile->photo)
        && (int) ($profile->photo_approved ?? 0) === 1
        && function_exists('show_profile_picture')
        && show_profile_picture($profile);
    $photoUrl = $canShowPhoto ? uploaded_asset($profile->photo) : static_asset($avatarPath);
    $displayName = trim((string) ($profile->first_name ?? ''));
    $activePackageName = trim((string) (optional(optional($profile->member)->package)->name ?? ''));
    $isEnglish = app()->getLocale() === 'en';
    $packageNameMap = [
        'أسبوعي' => 'Weekly',
        'اسبوعي' => 'Weekly',
        'اشتراك أسبوعي' => 'Weekly plan',
        'اشتراك اسبوعي' => 'Weekly plan',
        'شهري' => 'Monthly',
        'اشتراك شهري' => 'Monthly plan',
        'ثلاثة أشهر' => '3 months',
        '3 أشهر' => '3 months',
        'اشتراك 3 أشهر' => '3-month plan',
        'اشتراك ثلاثة أشهر' => '3-month plan',
        'عضو مميز' => 'Featured member',
    ];
    $displayPackageName = $isEnglish ? ($packageNameMap[$activePackageName] ?? $activePackageName) : $activePackageName;
    $displayName = $displayName !== '' ? $displayName : ($isEnglish ? 'Wesal member' : 'عضو وصال');
@endphp

<article class="ws-member-card">
    <div class="ws-member-photo-wrap">
        <img
            class="ws-member-photo"
            src="{{ $photoUrl }}"
            alt="{{ $isEnglish ? 'Profile photo of ' . $displayName : 'الصورة الشخصية لـ ' . $displayName }}"
            loading="lazy"
            decoding="async"
            onerror="this.onerror=null;this.src='{{ static_asset($avatarPath) }}';"
        >
        @if (!empty($featured))
            <span class="ws-member-featured"><i class="las la-crown" aria-hidden="true"></i> {{ $isEnglish ? 'Featured member' : 'عضو مميز' }}</span>
        @endif
        <span class="ws-member-photo-shade" aria-hidden="true"></span>
    </div>
    <div class="ws-member-details">
        <h3 class="ws-member-name">{{ $displayName }}</h3>
        @if (!empty($featured))
            <div class="ws-package-name" title="{{ $displayPackageName ?: ($isEnglish ? 'Featured member' : 'عضو مميز') }}">
                <i class="las la-gem" aria-hidden="true"></i>
                <span>{{ $displayPackageName ?: ($isEnglish ? 'Featured member' : 'عضو مميز') }}</span>
            </div>
        @else
            <div class="ws-member-subtitle">{{ $isEnglish ? ($memberGender == 2 ? 'Wesal member' : 'Wesal member') : ($memberGender == 2 ? 'إحدى عضوات وصال' : 'أحد أعضاء وصال') }}</div>
        @endif
        <a class="ws-contact-cta" href="#download"
            data-android-url="{{ $playStoreUrl ?? '' }}"
            data-ios-url="{{ $appStoreUrl ?? '' }}"
            onclick="return wsOpenAppDownload(event, this)">
            <i class="las la-paper-plane" aria-hidden="true"></i>
            <span>{{ $isEnglish ? 'Request contact' : 'اطلب التواصل' }}</span>
        </a>
    </div>
</article>
