<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;

class SetWebsiteLocale
{
    /**
     * Resolve one stable public-site locale for the complete request.
     *
     * A direct response cookie is used for the explicit query override. This
     * middleware runs after AddQueuedCookiesToResponse in the web stack, so
     * Cookie::queue() here would be too late to be attached by that middleware.
     */
    public function handle(Request $request, Closure $next)
    {
        // Do not interfere with the admin panel or installer/update flows.
        if ($request->is('admin', 'admin/*', 'install', 'install/*', 'update', 'update/*')) {
            return $next($request);
        }

        $queryLocale = strtolower((string) $request->query('wesal_locale', ''));
        $cookieLocale = strtolower((string) $request->cookie('wesal_website_locale', ''));
        $sessionLocale = strtolower((string) $request->session()->get('website_locale', ''));
        $legacyLocale = strtolower((string) $request->session()->get('locale', ''));

        if (in_array($queryLocale, ['ar', 'en'], true)) {
            // An explicit URL choice always wins over stale cookie/session state.
            $locale = $queryLocale;
        } elseif (in_array($cookieLocale, ['ar', 'en'], true)) {
            $locale = $cookieLocale;
        } elseif (in_array($sessionLocale, ['ar', 'en'], true)) {
            $locale = $sessionLocale;
        } elseif (in_array($legacyLocale, ['ar', 'en'], true)) {
            $locale = $legacyLocale;
        } else {
            $preferred = $request->getPreferredLanguage(['ar', 'en']);
            $locale = $preferred && strpos(strtolower($preferred), 'ar') === 0 ? 'ar' : 'en';
        }

        // One source of truth shared by controllers, Blade layouts and partials.
        $request->attributes->set('wesal_website_locale', $locale);
        $request->session()->put('website_locale', $locale);
        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);
        // Public pages contain locale-specific HTML and member cards. Never let
        // an intermediary cache reuse one visitor's language for another.
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Vary', 'Cookie, Accept-Language');

        // Attach the direct-link locale cookie to THIS response. Queued cookies
        // are already collected earlier in the web middleware pipeline.
        if (in_array($queryLocale, ['ar', 'en'], true)) {
            $response->headers->setCookie(cookie(
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
        }

        return $response;
    }
}
