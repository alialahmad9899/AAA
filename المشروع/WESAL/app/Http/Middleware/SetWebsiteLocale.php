<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;

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

        $locale = strtolower((string) $request->session()->get('locale', ''));

        if (!in_array($locale, ['ar', 'en'], true)) {
            $preferred = $request->getPreferredLanguage(['ar', 'en']);
            $locale = $preferred && strpos(strtolower($preferred), 'ar') === 0
                ? 'ar'
                : 'en';

            $request->session()->put('locale', $locale);
        }

        App::setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
