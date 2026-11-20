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
        $this->assertStringContainsString("->session()->get('website_locale'", $middleware);
        $this->assertStringContainsString('$request->cookie(\'wesal_website_locale\'', $middleware);
        $this->assertStringContainsString("->session()->get('locale'", $middleware);
        $this->assertStringContainsString('$request->session()->put(\'website_locale\', $locale)', $middleware);
        $this->assertStringContainsString('$request->session()->put(\'locale\', $locale)', $middleware);
        $this->assertStringContainsString('App::setLocale($locale)', $middleware);
        $this->assertStringContainsString("Content-Language", $middleware);
        $this->assertStringContainsString("admin/*", $middleware);
        $this->assertStringContainsString("update/*", $middleware);
    }

    public function test_manual_language_selection_is_saved_in_a_dedicated_cookie_and_session(): void
    {
        $controller = $this->source('app/Http/Controllers/LanguageController.php');
        $middleware = $this->source('app/Http/Middleware/SetWebsiteLocale.php');

        $this->assertStringContainsString('$request->session()->put(\'website_locale\', $locale)', $controller);
        $this->assertStringContainsString('$request->session()->put(\'locale\', $locale)', $controller);
        $this->assertStringContainsString('->session()->save()', $controller);
        $this->assertStringContainsString("cookie(", $controller);
        $this->assertStringContainsString("'wesal_website_locale'", $controller);
        $this->assertGreaterThan(
            strpos($controller, "cookie("),
            strpos($controller, "'wesal_website_locale'")
        );
        $this->assertStringContainsString('$request->cookie(\'wesal_website_locale\'', $middleware);
        $this->assertStringContainsString("'private, no-store, no-cache, must-revalidate, max-age=0'", $middleware);
        $this->assertStringContainsString("'Vary', 'Cookie'", $middleware);
    }

    public function test_support_page_is_standalone_and_uses_the_transparent_wesal_logo(): void
    {
        $layout = $this->source('resources/views/frontend/layouts/app.blade.php');
        $contact = $this->source('resources/views/frontend/contact_us.blade.php');

        $this->assertStringContainsString('$isWesalSupportPage = Route::currentRouteName() === \'contact_us\'', $layout);
        $this->assertStringContainsString('!$isWesalSupportPage', $layout);
        $this->assertStringContainsString("frontend.inc.header", $layout);
        $this->assertStringContainsString("frontend.inc.footer", $layout);
        $this->assertStringContainsString("frontend.inc.language_switcher", $contact);
        $this->assertStringContainsString("assets/img/wesal-legal-logo.png", $contact);
        $this->assertStringContainsString("We are here", $contact);
        $this->assertStringContainsString("Send a message to the Wesal team", $contact);
    }

    public function test_homepage_uses_the_transparent_wesal_logo_at_top_and_bottom(): void
    {
        $home = $this->source('resources/views/frontend/index.blade.php');
        $logoAsset = dirname(__DIR__, 2) . '/public/assets/img/wesal-legal-logo.png';

        $this->assertFileExists($logoAsset, 'The transparent Wesal logo asset must be present.');
        $this->assertSame(2, substr_count($home, "static_asset('assets/img/wesal-legal-logo.png')"));
        $this->assertStringNotContainsString('ws-wordmark', $home);
        $this->assertStringContainsString('ws-home-logo-link', $home);
        $this->assertStringContainsString('ws-footer-brand', $home);
    }

    public function test_language_switcher_accepts_only_arabic_and_english_and_returns_locally(): void
    {
        $controller = $this->source('app/Http/Controllers/LanguageController.php');
        $routes = $this->source('routes/web.php');
        $switcher = $this->source('resources/views/frontend/inc/language_switcher.blade.php');

        $this->assertStringContainsString("Rule::in(['ar', 'en'])", $controller);
        $this->assertStringContainsString("'redirect_to' => ['nullable', 'string', 'max:2048']", $controller);
        $this->assertStringContainsString('strpos($path, \'//\') === 0', $controller);
        $this->assertStringContainsString("Route::get('/language'", $routes);
        $this->assertStringContainsString("Route::post('/language'", $routes);
        $this->assertStringContainsString('method="GET"', $switcher);
        $this->assertStringContainsString("middleware('throttle:30,1')", $routes);
        $this->assertStringContainsString("value=\"ar\"", $switcher);
        $this->assertStringContainsString("value=\"en\"", $switcher);
        $this->assertStringContainsString("request()->getRequestUri()", $switcher);
    }

    public function test_browser_locale_middleware_runs_after_session_start_for_web_requests(): void
    {
        $kernel = $this->source('app/Http/Kernel.php');
        $routes = $this->source('routes/web.php');

        $webGroupStart = strpos($kernel, "'web' => [");
        $apiGroupStart = strpos($kernel, "'api' => [", $webGroupStart === false ? 0 : $webGroupStart);
        $this->assertNotFalse($webGroupStart, 'The Laravel web middleware group must exist.');
        $this->assertNotFalse($apiGroupStart, 'The Laravel API middleware group must follow the web group.');

        $webGroup = substr($kernel, $webGroupStart, $apiGroupStart - $webGroupStart);
        $sessionPosition = strpos($webGroup, '\\Illuminate\\Session\\Middleware\\StartSession::class,');
        $localePosition = strpos($webGroup, '\\App\\Http\\Middleware\\SetWebsiteLocale::class,');

        $this->assertNotFalse($sessionPosition, 'The web group must start the session.');
        $this->assertNotFalse($localePosition, 'The website locale middleware must be attached to the web group, not merely registered as an alias.');
        $this->assertGreaterThan($sessionPosition, $localePosition, 'The locale middleware must run after the session starts.');

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
        $this->assertStringContainsString('Choose the store that suits your device', $home);
        $this->assertStringNotContainsString('اختر المتجر المناسب لجهازك، و{{ $isEnglish', $home);
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
        $this->assertStringContainsString('8 سنوات وفق § 147 AO و§ 257 HGB', $arabic);
        $this->assertStringContainsString('10 سنوات وفق § 147 AO و§ 257 HGB', $arabic);
        $this->assertStringContainsString('6 سنوات عندما تنطبق مدة الحفظ القانونية البالغة ست سنوات', $arabic);
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

    public function test_public_website_login_and_registration_are_disabled(): void
    {
        $routes = $this->source('routes/web.php');
        $adminRoutes = $this->source('routes/admin.php');
        $apiRoutes = $this->source('routes/api.php');
        $home = $this->source('resources/views/frontend/index.blade.php');
        $login = $this->source('app/Http/Controllers/Auth/LoginController.php');
        $register = $this->source('app/Http/Controllers/Auth/RegisterController.php');

        $this->assertSame(1, substr_count($routes, 'Auth::routes('));
        $this->assertStringContainsString("Auth::routes(['verify' => true, 'register' => false, 'reset' => false])", $routes);
        $this->assertStringContainsString("Route::match(['GET', 'POST'], '/login'", $routes);
        $this->assertStringContainsString("->name('login')", $routes);
        $this->assertStringContainsString("Route::match(['GET', 'POST'], '/register'", $routes);
        $this->assertStringContainsString("->name('register')", $routes);
        $this->assertStringContainsString("'/users/login'", $routes);
        $this->assertStringContainsString("'/registration/verification-code-send'", $routes);
        $this->assertStringContainsString("'/registration/verification-code-confirmation'", $routes);
        $this->assertStringNotContainsString("route('login')", $home);
        $this->assertStringNotContainsString("route('register')", $home);
        $this->assertStringNotContainsString('تسجيل الدخول', $home);
        $this->assertStringNotContainsString('إنشاء حساب', $home);
        $this->assertStringContainsString("request()->get('query') !== 'mobile_app'", $login);
        $this->assertStringContainsString("session('login_from') !== 'mobile_app'", $login);
        $this->assertStringContainsString('public function showLoginForm()', $login);
        $this->assertStringContainsString('abort(404);', $login);
        $this->assertStringContainsString('public function showRegistrationForm()', $register);
        $this->assertStringContainsString('abort(404);', $register);
        $this->assertStringContainsString('Public website registration is disabled', $register);
        $this->assertStringContainsString("Route::get('/admin/login'", $adminRoutes);
        $this->assertStringContainsString("Route::post('/signup'", $apiRoutes);
        $this->assertStringContainsString("Route::post('/signin'", $apiRoutes);
        $this->assertStringContainsString("Route::post('/auth/google'", $apiRoutes);
    }

    public function test_english_legal_documents_are_real_english_only_view_files(): void
    {
        foreach ([
            'resources/views/frontend/legal/en/privacy_policy.blade.php',
            'resources/views/frontend/legal/en/terms_conditions.blade.php',
        ] as $path) {
            $english = $this->source($path);
            $this->assertNotSame('', trim($english), "{$path} must not be empty.");
            $this->assertDoesNotMatchRegularExpression('/[\\x{0600}-\\x{06FF}]/u', $english);
        }

        $home = $this->source('resources/views/frontend/index.blade.php');
        $contact = $this->source('resources/views/frontend/contact_us.blade.php');
        $this->assertStringContainsString('$isEnglish = $websiteLocale === \'en\';', $home);
        $this->assertStringContainsString('$isEnglish = $websiteLocale === \'en\';', $contact);
        $this->assertStringContainsString('Wesal | A serious marriage platform', $home);
        $this->assertStringContainsString('Help & Support', $contact);
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

    public function test_explicit_language_query_override_takes_precedence_over_stale_cookie_and_session(): void
    {
        $middleware = $this->source('app/Http/Middleware/SetWebsiteLocale.php');
        $queryLocalePosition = strpos($middleware, "\$request->query('wesal_locale'");
        $queryBranchPosition = strpos($middleware, "if (in_array(\$queryLocale, ['ar', 'en'], true))");
        $cookieBranchPosition = strpos($middleware, "} elseif (in_array(\$cookieLocale, ['ar', 'en'], true))");

        $this->assertNotFalse($queryLocalePosition, 'The middleware must read the one-time locale override.');
        $this->assertNotFalse($queryBranchPosition, 'The explicit locale override branch must exist.');
        $this->assertNotFalse($cookieBranchPosition, 'The saved locale cookie branch must remain available.');
        $this->assertLessThan($cookieBranchPosition, $queryBranchPosition, 'The explicit choice must win over a stale cookie.');
        $this->assertStringContainsString("\$response->headers->setCookie(cookie(", $middleware);
        $this->assertStringContainsString("'wesal_website_locale'", $middleware);
    }

    public function test_language_switch_redirect_carries_the_selected_locale_to_the_destination_request(): void
    {
        $controller = $this->source('app/Http/Controllers/LanguageController.php');
        $this->assertStringContainsString("\$queryParameters['wesal_locale'] = \$locale;", $controller);
        $this->assertStringContainsString('http_build_query($queryParameters)', $controller);
        $this->assertStringContainsString('Only redirect to a local path.', $controller);
    }


    public function test_explicit_locale_is_shared_as_one_request_attribute_and_cookie_is_attached_after_next(): void
    {
        $middleware = $this->source('app/Http/Middleware/SetWebsiteLocale.php');

        $this->assertStringContainsString("\$request->attributes->set('wesal_website_locale', \$locale)", $middleware);
        $this->assertStringContainsString("if (in_array(\$queryLocale, ['ar', 'en'], true))", $middleware);
        $this->assertStringContainsString("\$response->headers->setCookie(cookie(", $middleware);
        $this->assertStringNotContainsString('Cookie::queue(', $middleware);
        $this->assertStringContainsString("'private, no-store, no-cache, must-revalidate, max-age=0'", $middleware);
        $this->assertStringContainsString("'Content-Language'", $middleware);
    }

    public function test_homepage_uses_the_resolved_request_locale_instead_of_re_reading_global_locale(): void
    {
        $controller = $this->source('app/Http/Controllers/HomeController.php');
        $home = $this->source('resources/views/frontend/index.blade.php');
        $layout = $this->source('resources/views/frontend/layouts/app.blade.php');
        $card = $this->source('resources/views/frontend/inc/marketing_member_card.blade.php');

        $this->assertStringContainsString("'wesal_website_locale'", $controller);
        $this->assertStringContainsString("'websiteLocale'", $controller);
        $this->assertStringContainsString('$isEnglish = $websiteLocale === \'en\';', $home);
        $this->assertStringContainsString('data-wesal-locale="{{ $websiteLocale }}"', $layout);
        $this->assertStringContainsString('dir="{{ $websiteIsArabic ? \'rtl\' : \'ltr\' }}"', $layout);
        $this->assertStringContainsString('<h3 class="ws-member-name" dir="auto">', $card);
    }

    public function test_arabic_landing_text_cards_and_member_names_have_explicit_rtl_layout_rules(): void
    {
        $home = $this->source('resources/views/frontend/index.blade.php');

        foreach ([
            '.ws-landing-ar {',
            'direction: rtl !important;',
            '.ws-landing-ar .ws-header-inner',
            '.ws-landing-ar .ws-hero-grid',
            '.ws-landing-ar .ws-members-grid',
            '.ws-landing-ar .ws-member-card',
            '.ws-landing-ar .ws-member-name',
            'text-align: right;',
            'unicode-bidi: plaintext;',
        ] as $rule) {
            $this->assertStringContainsString($rule, $home);
        }
    }

}
