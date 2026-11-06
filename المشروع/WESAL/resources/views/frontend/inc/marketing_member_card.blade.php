@php
    $memberGender = optional($profile->member)->gender;
    $avatarPath = $memberGender == 2 ? 'assets/img/female-avatar-place.png' : 'assets/img/avatar-place.png';
    $canShowPhoto = !empty($profile->photo)
        && (int) ($profile->photo_approved ?? 0) === 1
        && function_exists('show_profile_picture')
        && show_profile_picture($profile);
    $photoUrl = $canShowPhoto ? uploaded_asset($profile->photo) : static_asset($avatarPath);
    $displayName = trim((string) ($profile->first_name ?? ''));
    if ($displayName === '') {
        $displayName = 'عضو وصال';
    }
    $activePackageName = trim((string) (optional(optional($profile->member)->package)->name ?? ''));
@endphp

<article class="ws-member-card">
    <div class="ws-member-photo-wrap">
        <img
            class="ws-member-photo"
            src="{{ $photoUrl }}"
            alt="{{ 'الصورة الشخصية لـ ' . $displayName }}"
            loading="lazy"
            decoding="async"
            onerror="this.onerror=null;this.src='{{ static_asset($avatarPath) }}';"
        >
        @if (!empty($featured))
            <span class="ws-member-featured"><i class="las la-crown" aria-hidden="true"></i> عضو مميز</span>
        @endif
        <span class="ws-member-photo-shade" aria-hidden="true"></span>
    </div>
    <div class="ws-member-details">
        <h3 class="ws-member-name">{{ $displayName }}</h3>
        @if (!empty($featured))
            <div class="ws-package-name" title="{{ $activePackageName ?: 'عضو مميز' }}">
                <i class="las la-gem" aria-hidden="true"></i>
                <span>{{ $activePackageName ?: 'عضو مميز' }}</span>
            </div>
        @else
            <div class="ws-member-subtitle">{{ $memberGender == 2 ? 'إحدى عضوات وصال' : 'أحد أعضاء وصال' }}</div>
        @endif
        <a class="ws-contact-cta" href="#download"
            data-android-url="{{ $playStoreUrl ?? '' }}"
            data-ios-url="{{ $appStoreUrl ?? '' }}"
            onclick="return wsOpenAppDownload(event, this)">
            <i class="las la-paper-plane" aria-hidden="true"></i>
            <span>اطلب التواصل</span>
        </a>
    </div>
</article>
