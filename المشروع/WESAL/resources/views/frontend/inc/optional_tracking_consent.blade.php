@php
    $consentLanguage = app()->getLocale() === 'en' ? 'en' : 'ar';
    $optionalTrackingEnabled = get_setting('google_analytics_activation') == 1 || get_setting('facebook_pixel_activation') == 1;
@endphp
@if ($optionalTrackingEnabled)
    <aside id="wesal-optional-consent" class="wesal-consent-banner" role="dialog" aria-live="polite" aria-labelledby="wesal-consent-title" hidden>
        <div class="wesal-consent-copy">
            <strong id="wesal-consent-title">{{ $consentLanguage === 'en' ? 'Your privacy choices' : 'خيارات الخصوصية' }}</strong>
            <p>{{ $consentLanguage === 'en'
                ? 'Wesal uses essential technologies to operate the website. With your permission, we may also use analytics and advertising technologies to understand website usage and measure campaigns. You can accept or reject these optional technologies.'
                : 'يستخدم وصال التقنيات الضرورية لتشغيل الموقع. وبموافقتك، قد نستخدم أيضاً تقنيات التحليل والإعلانات لفهم استخدام الموقع وقياس الحملات. يمكنك قبول هذه التقنيات الاختيارية أو رفضها.' }}</p>
            <a href="{{ route('legal.privacy-policy') }}">{{ $consentLanguage === 'en' ? 'Read the Privacy Policy' : 'اقرأ سياسة الخصوصية' }}</a>
        </div>
        <div class="wesal-consent-actions">
            <button type="button" id="wesal-consent-accept">{{ $consentLanguage === 'en' ? 'Accept optional' : 'قبول الاختياري' }}</button>
            <button type="button" id="wesal-consent-reject">{{ $consentLanguage === 'en' ? 'Reject optional' : 'رفض الاختياري' }}</button>
        </div>
    </aside>
    <style>
        .wesal-consent-banner {
            position: fixed; z-index: 2000; inset-inline: 14px; bottom: calc(14px + env(safe-area-inset-bottom));
            display: flex; align-items: center; justify-content: space-between; gap: 18px;
            max-width: 920px; margin-inline: auto; padding: 16px 18px;
            border: 1px solid #D9E3E0; border-radius: 18px; background: #fff;
            color: #1D2D44; box-shadow: 0 14px 45px rgba(15,45,55,.18);
            font-family: {{ $consentLanguage === 'en' ? "'Inter', Arial, sans-serif" : "'Cairo', Tahoma, sans-serif" }};
        }
        .wesal-consent-banner[hidden] { display: none !important; }
        .wesal-consent-copy { min-width: 0; }
        .wesal-consent-copy strong { display: block; margin-bottom: 4px; color: #0F4C5C; font-size: 14px; font-weight: 900; }
        .wesal-consent-copy p { margin: 0 0 5px; color: #465566; font-size: 12px; line-height: 1.8; }
        .wesal-consent-copy a { color: #14596A; font-size: 11px; font-weight: 800; text-decoration: underline; }
        .wesal-consent-actions { display: flex; flex: 0 0 auto; flex-wrap: wrap; gap: 7px; }
        .wesal-consent-actions button { min-height: 39px; padding: 8px 12px; border: 1px solid #0F4C5C; border-radius: 10px; background: #0F4C5C; color: #fff; font: inherit; font-size: 11px; font-weight: 800; cursor: pointer; }
        .wesal-consent-actions button:last-child { background: #fff; color: #0F4C5C; }
        @media (max-width: 640px) {
            .wesal-consent-banner { align-items: stretch; flex-direction: column; gap: 11px; padding: 13px; }
            .wesal-consent-actions { display: grid; grid-template-columns: 1fr 1fr; }
            .wesal-consent-actions button { min-height: 42px; }
        }
    </style>
@endif
