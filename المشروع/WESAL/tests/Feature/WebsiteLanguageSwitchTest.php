<?php

namespace Tests\Feature;

use App\Http\Middleware\SetWebsiteLocale;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class WebsiteLanguageSwitchTest extends TestCase
{
    public function test_explicit_language_query_overrides_an_existing_arabic_cookie_and_session(): void
    {
        $request = Request::create(
            '/?wesal_locale=en',
            'GET',
            ['wesal_locale' => 'en'],
            ['wesal_website_locale' => 'ar']
        );

        $session = new Store('wesal_test_session', new ArraySessionHandler(120));
        $session->start();
        $session->put('website_locale', 'ar');
        $session->put('locale', 'ar');
        $request->setLaravelSession($session);

        $response = (new SetWebsiteLocale())->handle(
            $request,
            static function () {
                return response(app()->getLocale());
            }
        );

        $this->assertSame('en', $response->getContent());
        $this->assertSame('en', app()->getLocale());
        $this->assertSame('en', $session->get('website_locale'));
        $this->assertSame('en', $session->get('locale'));
    }

    public function test_arabic_query_override_can_switch_back_from_english(): void
    {
        $request = Request::create(
            '/?wesal_locale=ar',
            'GET',
            ['wesal_locale' => 'ar'],
            ['wesal_website_locale' => 'en']
        );

        $session = new Store('wesal_test_session_ar', new ArraySessionHandler(120));
        $session->start();
        $session->put('website_locale', 'en');
        $session->put('locale', 'en');
        $request->setLaravelSession($session);

        $response = (new SetWebsiteLocale())->handle(
            $request,
            static function () {
                return response(app()->getLocale());
            }
        );

        $this->assertSame('ar', $response->getContent());
        $this->assertSame('ar', app()->getLocale());
        $this->assertSame('ar', $session->get('website_locale'));
        $this->assertSame('ar', $session->get('locale'));
    }
}
