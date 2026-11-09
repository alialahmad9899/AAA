<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
        $this->configureRateLimiting();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapAdminRoutes();

        $this->mapSupportTicketRoutes();

        $this->mapOtpRoutes();

        $this->mapReferralRoutes();

        $this->mapWebRoutes();

        // $this->mapInstallRoutes();

        // $this->mapUpdateRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    protected function mapAdminRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/admin.php'));
    }

    protected function mapSupportTicketRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/support_tickets.php'));
    }

    protected function mapOtpRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/otp.php'));
    }

    protected function mapReferralRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/referral.php'));
    }

    protected function mapInstallRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/install.php'));
    }

    protected function mapUpdateRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/update.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            $identity = $request->user()
                ? 'user:' . $request->user()->getAuthIdentifier()
                : 'ip:' . ($request->ip() ?: 'unknown');

            // Keep routine app browsing responsive while avoiding an excessive
            // generic allowance of 1,000 requests per minute.
            return Limit::perMinute(300)->by($identity);
        });

        RateLimiter::for('auth-login', function (Request $request) {
            $ip = 'login-ip:' . ($request->ip() ?: 'unknown');
            $account = 'login-account:' . hash('sha256', strtolower(trim((string) $request->input('email_or_phone', 'unknown'))));

            return [
                Limit::perMinute(30)->by($ip),
                Limit::perMinute(5)->by($account),
            ];
        });

        RateLimiter::for('auth-google', function (Request $request) {
            return Limit::perMinute(15)->by('google-ip:' . ($request->ip() ?: 'unknown'));
        });

        RateLimiter::for('auth-signup', function (Request $request) {
            return Limit::perHour(15)->by('signup-ip:' . ($request->ip() ?: 'unknown'));
        });

        RateLimiter::for('password-reset-request', function (Request $request) {
            $ip = 'reset-request-ip:' . ($request->ip() ?: 'unknown');
            $identity = 'reset-request-account:' . hash('sha256', strtolower(trim((string) $request->input('email_or_phone', 'unknown'))));

            return [
                Limit::perHour(15)->by($ip),
                Limit::perHour(3)->by($identity),
            ];
        });

        RateLimiter::for('password-reset-attempt', function (Request $request) {
            $ip = 'reset-attempt-ip:' . ($request->ip() ?: 'unknown');
            $identity = 'reset-attempt-account:' . hash('sha256', strtolower(trim((string) $request->input('email_or_phone', 'unknown'))));

            return [
                Limit::perMinute(20)->by($ip),
                Limit::perHour(10)->by($identity),
            ];
        });

        RateLimiter::for('password-reset-web', function (Request $request) {
            $ip = 'web-reset-ip:' . ($request->ip() ?: 'unknown');
            $identity = 'web-reset-account:' . hash('sha256', strtolower(trim((string) $request->input('email', 'unknown'))));

            return [
                Limit::perHour(15)->by($ip),
                Limit::perHour(5)->by($identity),
            ];
        });

        RateLimiter::for('verification-code-check', function (Request $request) {
            $userId = $request->user() ? $request->user()->getAuthIdentifier() : 'guest';

            return [
                Limit::perMinute(20)->by('verification-check-ip:' . ($request->ip() ?: 'unknown')),
                Limit::perMinute(10)->by('verification-check-user:' . $userId),
            ];
        });

        RateLimiter::for('verification-resend', function (Request $request) {
            $userId = $request->user() ? $request->user()->getAuthIdentifier() : 'guest';

            return [
                Limit::perHour(15)->by('verification-resend-ip:' . ($request->ip() ?: 'unknown')),
                Limit::perHour(5)->by('verification-resend-user:' . $userId),
            ];
        });

        RateLimiter::for('registration-code-send', function (Request $request) {
            $recipient = strtolower(trim((string) ($request->input('email') ?: $request->input('phone', 'unknown'))));

            return [
                Limit::perHour(15)->by('registration-code-ip:' . ($request->ip() ?: 'unknown')),
                Limit::perHour(3)->by('registration-code-recipient:' . hash('sha256', $recipient)),
            ];
        });

        RateLimiter::for('uploader-upload', function (Request $request) {
            $userId = $request->user() ? $request->user()->getAuthIdentifier() : 'guest';

            return [
                Limit::perMinute(6)->by('upload-user-minute:' . $userId),
                Limit::perHour(30)->by('upload-user-hour:' . $userId),
                Limit::perMinute(20)->by('upload-ip-minute:' . ($request->ip() ?: 'unknown')),
            ];
        });

        RateLimiter::for('profile-match-maintenance', function (Request $request) {
            $userId = $request->user() ? $request->user()->getAuthIdentifier() : 'guest';

            return [
                Limit::perMinute(1)->by('profile-match-admin-ip:' . ($request->ip() ?: 'unknown')),
                Limit::perHour(4)->by('profile-match-admin-user:' . $userId),
            ];
        });

        RateLimiter::for('profile-match-personal', function (Request $request) {
            $userId = $request->user() ? $request->user()->getAuthIdentifier() : 'guest';

            return [
                Limit::perMinute(10)->by('profile-match-user:' . $userId),
                Limit::perMinute(20)->by('profile-match-ip:' . ($request->ip() ?: 'unknown')),
            ];
        });

        RateLimiter::for('registration-code-check', function (Request $request) {
            $recipient = strtolower(trim((string) ($request->input('email') ?: $request->input('phone', 'unknown'))));

            return [
                Limit::perMinute(20)->by('registration-check-ip:' . ($request->ip() ?: 'unknown')),
                Limit::perMinute(10)->by('registration-check-recipient:' . hash('sha256', $recipient)),
            ];
        });
    }
}
