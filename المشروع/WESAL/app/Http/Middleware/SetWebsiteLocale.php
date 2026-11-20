<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SetWebsiteLocale
{
    /**
     * Apply a saved language choice, or detect the visitor's browser language
     * on the first visit. Wesal's public bilingual pages support Arabic and
     * English independently of the admin-managed translation catalogue.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // The admin panel and installer/update flows keep their own language
        // behaviour. The public website uses only Arabic and English.
        if ($request->is('admin', 'admin/*', 'install', 'install/*', 'update', 'update/*')) {
            return $next($request);
        }

        // A dedicated cookie is the source of truth for a manually selected
        // website language. This prevents unrelated admin/app code that also
        // uses the generic `locale` session key from resetting the public site.
        // A one-time query override is added to the redirect target by
        // LanguageController. It makes the very first page after a switch use
        // the requested locale even when an old cookie is still present or a
        // hosting/browser cache interferes with the cookie update.
        $queryLocale = strtolower((string) $request->query('wesal_locale', ''));
        $cookieLocale = strtolower((string) $request->cookie('wesal_website_locale', ''));
        $sessionLocale = strtolower((string) $request->session()->get('website_locale', ''));
        $legacyLocale = strtolower((string) $request->session()->get('locale', ''));

        if (in_array($queryLocale, ['ar', 'en'], true)) {
            $locale = $queryLocale;

            // Persist the explicit choice for subsequent pages as well.
            Cookie::queue(cookie(
                'wesal_website_locale',
                $locale,
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure() || strtolower((string) $request->header('X-Forwarded-Proto')) === 'https',
                true,
                false,
                'lax'
            ));
        } elseif (in_array($cookieLocale, ['ar', 'en'], true)) {
            $locale = $cookieLocale;
        } elseif (in_array($sessionLocale, ['ar', 'en'], true)) {
            $locale = $sessionLocale;
        } elseif (in_array($legacyLocale, ['ar', 'en'], true)) {
            // Migrate an existing visitor's choice without losing continuity.
            $locale = $legacyLocale;
        } else {
            $preferred = $request->getPreferredLanguage(['ar', 'en']);
            $locale = $preferred && strpos(strtolower($preferred), 'ar') === 0
                ? 'ar'
                : 'en';
        }

        $request->session()->put('website_locale', $locale);
        // Keep compatibility with the existing website translation helpers.
        $request->session()->put('locale', $locale);

        App::setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);
        // Locale-sensitive HTML must not be served from a shared cache using
        // another visitor's language variant.
        $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
        $response->headers->set('Vary', 'Cookie', false);

        return $response;
    }
}
