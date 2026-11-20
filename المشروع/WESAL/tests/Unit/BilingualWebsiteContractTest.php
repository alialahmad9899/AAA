<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BilingualWebsiteContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content, "Unable to read {$relativePath}");
        return $content;
    }

    public function test_browser_language_detection_and_saved_language_are_supported(): void
    {
        $middleware = $this->source('app/Http/Middleware/SetWebsiteLocale.php');

        $this->assertStringContainsString("getPreferredLanguage(['ar', 'en'])", $middleware);
        $this->assertStringContainsString("['ar', 'en']", $middleware);
        $this->assertStringContainsString("session()->get('locale'", $middleware);
        $this->assertStringContainsString('session()->put(\'locale\', $locale)', $middleware);
        $this->assertStringContainsString('App::setLocale($locale)', $middleware);
        $this->assertStringContainsString("Content-Language", $middleware);
        $this->assertStringContainsString("admin/*", $middleware);
        $this->assertStringContainsString("update/*", $middleware);
    }

    public function test_language_switcher_accepts_only_arabic_and_english_and_returns_locally(): void
    {
        $controller = $this->source('app/Http/Controllers/LanguageController.php');
        $routes = $this->source('routes/web.php');
        $switcher = $this->source('resources/views/frontend/inc/language_switcher.blade.php');

        $this->assertStringContainsString("Rule::in(['ar', 'en'])", $controller);
        $this->assertStringContainsString("'redirect_to' => ['nullable', 'string', 'max:2048']", $controller);
        $this->assertStringContainsString('strpos($path, \'//\') === 0', $controller);
        $this->assertStringContainsString("Route::post('/language'", $routes);
        $this->assertStringContainsString("middleware('throttle:30,1')", $routes);
        $this->assertStringContainsString("value=\"ar\"", $switcher);
        $this->assertStringContainsString("value=\"en\"", $switcher);
        $this->assertStringContainsString("request()->getRequestUri()", $switcher);
    }

    public function test_browser_locale_middleware_is_applied_to_every_public_web_request(): void
    {
        $kernel = $this->source('app/Http/Kernel.php');
        $routes = $this->source('routes/web.php');

        $sessionPosition = strpos($kernel, 'StartSession::class,');
        $localePosition = strpos($kernel, 'SetWebsiteLocale::class,');
        $this->assertNotFalse($sessionPosition);
        $this->assertNotFalse($localePosition);
        $this->assertLessThan($localePosition, $sessionPosition);

        $this->assertStringContainsString("Route::get('/', 'index')->name('home')", $routes);
        $this->assertStringContainsString("Route::get('/contact-us/page', 'show_contact_us_page')->name('contact_us')", $routes);
        $this->assertStringContainsString("Route::post('/contact-us', 'store')->name('contact-us.store')", $routes);
        $this->assertStringContainsString("Route::view('/privacy-policy', 'frontend.legal.privacy_policy')->name('legal.privacy-policy')", $routes);
        $this->assertStringContainsString("Route::view('/terms-conditions', 'frontend.legal.terms_conditions')->name('legal.terms-conditions')", $routes);
    }

    public function test_homepage_and_shared_navigation_show_language_controls_on_mobile_and_desktop(): void
    {
        $home = $this->source('resources/views/frontend/index.blade.php');
        $header = $this->source('resources/views/frontend/inc/header.blade.php');
        $layout = $this->source('resources/views/frontend/layouts/app.blade.php');
        $footer = $this->source('resources/views/frontend/inc/footer.blade.php');

        $this->assertStringContainsString('ws-mobile-header-controls', $home);
        $this->assertStringContainsString("frontend.inc.language_switcher", $home);
        $this->assertStringContainsString("frontend.inc.language_switcher", $header);
        $this->assertStringContainsString('lang="{{ $websiteLocale }}" dir="{{ $websiteIsArabic ? \'rtl\' : \'ltr\' }}"', $layout);
        $this->assertStringContainsString('Privacy Policy', $home);
        $this->assertStringContainsString('General Terms of Use', $home);
        $this->assertStringContainsString('Cookie preferences', $footer);
    }

    public function test_homepage_translation_branches_are_not_nested_blade_expressions(): void
    {
        $home = $this->source('resources/views/frontend/index.blade.php');

        foreach ([
            ": '{{ $isEnglish",
            ': "زواج جاد {{ $isEnglish',
            'ثم {{ $isEnglish',
            'منصة {{ $isEnglish ?',
        ] as $nestedExpression) {
            $this->assertStringNotContainsString($nestedExpression, $home);
        }

        $this->assertStringContainsString("'وصال | منصة زواج جاد للعرب والسوريين حول العالم'", $home);
        $this->assertStringContainsString("'Wesal | A serious marriage platform for Arabs and Syrians worldwide'", $home);
        $this->assertStringContainsString("'Wesal. All rights reserved.'", $home);
    }

    public function test_privacy_policy_is_complete_in_both_languages_and_has_no_editorial_placeholders(): void
    {
        $arabic = $this->source('resources/views/frontend/legal/privacy_policy.blade.php');
        $english = $this->source('resources/views/frontend/legal/en/privacy_policy.blade.php');

        for ($section = 1; $section <= 24; $section++) {
            $this->assertStringContainsString('id="privacy-' . $section . '"', $arabic);
            $this->assertStringContainsString('id="privacy-' . $section . '"', $english);
        }

        foreach ([
            'يؤكده المبرمج',
            'يؤكده المحاسب',
            'مكتب المحاسبة أو المستشار الضريبي',
            '[MISSING]',
        ] as $placeholder) {
            $this->assertStringNotContainsString($placeholder, $arabic);
            $this->assertStringNotContainsString($placeholder, $english);
        }

        $this->assertStringContainsString('Google Analytics', $arabic);
        $this->assertStringContainsString('Meta Pixel', $arabic);
        $this->assertStringContainsString('قبول التقنيات الاختيارية', $arabic);
        $this->assertStringContainsString('Eight years', $english);
        $this->assertStringContainsString('Ten years', $english);
        $this->assertStringContainsString('Six years', $english);
        $this->assertStringContainsString('30 days', $english);
        $this->assertSame(4, substr_count($arabic, '<table class="legal-table">'));
        $this->assertSame(4, substr_count($english, '<table class="legal-table">'));
    }

    public function test_terms_are_complete_in_both_languages_and_include_the_model_withdrawal_form(): void
    {
        $arabic = $this->source('resources/views/frontend/legal/terms_conditions.blade.php');
        $english = $this->source('resources/views/frontend/legal/en/terms_conditions.blade.php');

        for ($section = 1; $section <= 15; $section++) {
            $this->assertStringContainsString('id="terms-' . $section . '"', $arabic);
            $this->assertStringContainsString('id="terms-' . $section . '"', $english);
        }

        $this->assertStringContainsString('terms-appendix', $arabic);
        $this->assertStringContainsString('terms-appendix', $english);
        $this->assertStringContainsString('نموذج إلغاء العقد', $arabic);
        $this->assertStringContainsString('Model withdrawal form', $english);
        $this->assertStringContainsString('wesalsyrien@gmail.com', $arabic);
        $this->assertStringContainsString('wesalsyrien@gmail.com', $english);
        $this->assertStringNotContainsString('href="https://www.wesalsyria.com/privacy"', $arabic);
        $this->assertStringNotContainsString('https://www.wesalsyria.com/privacy</a>', $arabic);
        $this->assertStringContainsString('privacy-policy', $arabic);
        $this->assertStringContainsString('privacy-policy', $english);
        $this->assertSame(2, substr_count($arabic, '<table class="legal-table">'));
        $this->assertSame(2, substr_count($english, '<table class="legal-table">'));
    }

    public function test_support_page_and_validation_messages_are_bilingual(): void
    {
        $contact = $this->source('resources/views/frontend/contact_us.blade.php');
        $request = $this->source('app/Http/Requests/ContactUsRequest.php');

        foreach ([
            'Help & Support',
            'The Wesal team is here',
            'Technical support',
            'Plans and payments',
            'Feedback and suggestions',
            'Send message',
        ] as $expected) {
            $this->assertStringContainsString($expected, $contact);
        }
        $this->assertStringContainsString('dir="{{ $isEnglish ? \'ltr\' : \'rtl\' }}"', $contact);
        $this->assertStringContainsString("app()->getLocale() === 'en'", $request);
        $this->assertStringContainsString('Please enter a valid email address.', $request);
        $this->assertStringContainsString('يرجى إدخال بريد إلكتروني صحيح.', $request);
    }

    public function test_optional_tracking_is_blocked_until_consent_and_can_be_changed_later(): void
    {
        $layout = $this->source('resources/views/frontend/layouts/app.blade.php');
        $banner = $this->source('resources/views/frontend/inc/optional_tracking_consent.blade.php');
        $privacyEnglish = $this->source('resources/views/frontend/legal/en/privacy_policy.blade.php');

        $this->assertStringContainsString("wesal_optional_tracking_consent_v1", $layout);
        $this->assertStringContainsString("choice === 'accepted'", $layout);
        $this->assertStringContainsString("choice === 'rejected'", $layout);
        $this->assertStringContainsString('wesalOpenCookiePreferences', $layout);
        $this->assertStringContainsString('window.wesalDisableOptionalTracking', $layout);
        $this->assertStringContainsString("window.fbq('consent', 'revoke')", $layout);
        $this->assertStringContainsString("analytics_storage: 'denied'", $layout);
        $this->assertStringContainsString("ad_storage: (wesalMetaPixelEnabled && wesalMetaPixelId) ? 'granted' : 'denied'", $layout);
        $this->assertStringContainsString('Accept optional', $banner);
        $this->assertStringContainsString('Reject optional', $banner);
        $this->assertStringContainsString('Google Analytics', $privacyEnglish);
        $this->assertStringContainsString('Meta Pixel', $privacyEnglish);
    }

    public function test_deleted_account_chat_messages_are_purged_after_the_policy_retention_period(): void
    {
        $command = $this->source('app/Console/Commands/PurgeDeletedChatThreads.php');
        $kernel = $this->source('app/Console/Kernel.php');
        $privacy = $this->source('resources/views/frontend/legal/en/privacy_policy.blade.php');

        $this->assertStringContainsString("ChatThread::onlyTrashed()", $command);
        $this->assertStringContainsString("withTrashed()", $command);
        $this->assertStringContainsString("whereNotNull('users.deleted_at')", $command);
        $this->assertStringContainsString('where(\'users.deleted_at\', \'<=\', $cutoff)', $command);
        $this->assertStringContainsString("chats()->withTrashed()->forceDelete()", $command);
        $this->assertStringContainsString('$thread->forceDelete()', $command);
        $this->assertStringContainsString("wesal:purge-deleted-chat-threads')->dailyAt('02:45')", $kernel);
        $this->assertStringContainsString('permanently removed by the scheduled cleanup after 30 days', $privacy);
    }
}
