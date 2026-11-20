<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LegalDocumentPagesContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content, "Unable to read {$relativePath}");
        return $content;
    }

    public function test_legal_pages_have_dedicated_routes_before_custom_page_catch_all(): void
    {
        $routes = $this->source('routes/web.php');

        $privacyRoute = "Route::view('/privacy-policy', 'frontend.legal.privacy_policy')->name('legal.privacy-policy')";
        $termsRoute = "Route::view('/terms-conditions', 'frontend.legal.terms_conditions')->name('legal.terms-conditions')";
        $legacyRedirect = "Route::redirect('/privacy', '/privacy-policy', 301)->name('legal.privacy-legacy')";
        $catchAll = "Route::get('/{slug}', 'PageController@show_custom_page')->name('custom-pages.show_custom_page')";

        $this->assertStringContainsString($privacyRoute, $routes);
        $this->assertStringContainsString($termsRoute, $routes);
        $this->assertStringContainsString($legacyRedirect, $routes);
        $this->assertLessThan(strpos($routes, $catchAll), strpos($routes, $privacyRoute));
        $this->assertLessThan(strpos($routes, $catchAll), strpos($routes, $termsRoute));
    }

    public function test_legal_layout_is_standalone_and_uses_the_wesal_brand(): void
    {
        $layout = $this->source('resources/views/frontend/layouts/legal.blade.php');

        $this->assertStringNotContainsString("frontend.layouts.app", $layout);
        $this->assertStringNotContainsString("frontend.inc.header", $layout);
        $this->assertStringNotContainsString("frontend.inc.footer", $layout);
        $this->assertStringNotContainsString("@include('frontend.inc.header')", $layout);
        $this->assertStringNotContainsString("@include('frontend.inc.footer')", $layout);
        $this->assertStringContainsString("assets/img/wesal-legal-logo.png", $layout);
        $this->assertStringContainsString("name=\"viewport\"", $layout);
        $this->assertStringContainsString(".legal-table tbody tr", $layout);
        $this->assertStringContainsString("grid-template-columns", $layout);
        $this->assertStringContainsString("@media (max-width: 680px)", $layout);

        $this->assertFileExists(dirname(__DIR__, 2) . '/public/assets/img/wesal-legal-logo.png');
    }

    public function test_privacy_page_contains_all_source_sections_and_disclosure_tables(): void
    {
        $privacy = $this->source('resources/views/frontend/legal/privacy_policy.blade.php');

        for ($section = 1; $section <= 24; $section++) {
            $this->assertStringContainsString('id="privacy-' . $section . '"', $privacy);
        }

        foreach ([
            'DSGVO',
            'BDSG',
            'TDDDG',
            'البيانات الحساسة',
            'Firebase Cloud Messaging (Google)',
            'Stripe Payments Europe Ltd.',
            '90 يوماً',
            '12 شهراً بعد إغلاق البلاغ',
            '30 يوماً',
            'Devrientstraße 5, 01067 Dresden',
            'تنظيف يومية مجدولة',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $privacy);
        }

        foreach ([
            'يؤكده المبرمج',
            'يؤكده المحاسب',
            'مكتب المحاسبة أو المستشار الضريبي',
        ] as $editorialPlaceholder) {
            $this->assertStringNotContainsString($editorialPlaceholder, $privacy);
        }

        $this->assertSame(4, substr_count($privacy, '<table class="legal-table">'));
    }

    public function test_terms_page_contains_all_source_sections_and_price_tables(): void
    {
        $terms = $this->source('resources/views/frontend/legal/terms_conditions.blade.php');

        for ($section = 1; $section <= 15; $section++) {
            $this->assertStringContainsString('id="terms-' . $section . '"', $terms);
        }

        foreach ([
            'zahlungspflichtig bestellen',
            'Widerruf',
            '14 يوماً',
            'Stripe',
            '10 USD',
            '20 USD',
            '50 USD',
            '100 USD',
            '25 USD',
            '§ 306 BGB',
            'قبل 6 أسابيع',
            'wesalsyrien@gmail.com',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $terms);
        }

        $this->assertSame(2, substr_count($terms, '<table class="legal-table">'));
        $this->assertStringContainsString('https://www.wesalsyria.com/privacy-policy', $terms);
    }

    public function test_homepage_and_shared_footer_link_to_each_document_separately(): void
    {
        $homepage = $this->source('resources/views/frontend/index.blade.php');
        $footer = $this->source('resources/views/frontend/inc/footer.blade.php');

        foreach ([$homepage, $footer] as $view) {
            $this->assertStringContainsString("route('legal.privacy-policy')", $view);
            $this->assertStringContainsString("route('legal.terms-conditions')", $view);
            $this->assertStringContainsString('سياسة الخصوصية', $view);
            $this->assertStringContainsString('شروط الاستخدام العامة', $view);
        }

        $this->assertStringNotContainsString('سياسة الخصوصية والشروط والأحكام', $homepage);
    }
}
