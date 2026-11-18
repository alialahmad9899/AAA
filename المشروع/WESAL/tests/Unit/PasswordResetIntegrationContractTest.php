<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PasswordResetIntegrationContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__ . '/../../' . $relativePath);
        self::assertIsString($source);
        return $source;
    }

    private function methodBody(string $source, string $start, string $end): string
    {
        $startAt = strpos($source, $start);
        self::assertNotFalse($startAt, 'Missing method: ' . $start);
        $endAt = strpos($source, $end, $startAt + strlen($start));
        self::assertNotFalse($endAt, 'Missing method terminator: ' . $end);
        return substr($source, $startAt, $endAt - $startAt);
    }

    public function test_mobile_reset_routes_are_public_and_rate_limited(): void
    {
        $routes = $this->source('routes/api.php');
        self::assertStringContainsString("Route::post('/forgot/password', 'AuthController@forgotPassword')->middleware('throttle:password-reset-request');", $routes);
        self::assertStringContainsString("Route::post('/reset/password', 'AuthController@resetPassword')->middleware('throttle:password-reset-attempt');", $routes);
    }

    public function test_password_reset_mail_transport_errors_are_not_silenced(): void
    {
        $utility = $this->source('app/Utility/EmailUtility.php');
        $method = $this->methodBody($utility, 'public static function password_reset_email(', 'public static function email_on_request(');
        self::assertStringContainsString('filter_var', $method);
        self::assertStringContainsString('get_email_template', $method);
        self::assertStringContainsString('Notification::send(', $method);
        self::assertStringNotContainsString('catch(', $method);
        self::assertStringNotContainsString('catch (', $method);
    }

    public function test_api_invalidates_reset_code_and_returns_503_when_delivery_fails(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/AuthController.php');
        $method = $this->methodBody($controller, 'public function forgotPassword(', 'public function verifyCode(');
        self::assertStringContainsString('catch (\\Throwable $exception)', $method);
        self::assertStringContainsString('$user->password_reset_code_hash = null;', $method);
        self::assertStringContainsString('$user->password_reset_code_expires_at = null;', $method);
        self::assertStringContainsString('], 503);', $method);
        self::assertStringNotContainsString('$user->verification_code = $issuedCode', $method);
    }

    public function test_password_recovery_secrets_are_hidden_from_serialized_user(): void
    {
        $user = $this->source('app/Models/User.php');
        self::assertStringContainsString("'verification_code'", $user);
        self::assertStringContainsString("'password_reset_code_hash'", $user);
        self::assertStringContainsString("'password_reset_code_expires_at'", $user);
    }

    public function test_mobile_password_change_is_authenticated_and_revokes_other_tokens(): void
    {
        $routes = $this->source('routes/api.php');
        $controller = $this->source('app/Http/Controllers/Api/ProfileController.php');
        $method = $this->methodBody($controller, 'public function password_update(', 'public function account_deactivation(');
        self::assertStringContainsString("Route::post('/change/password', 'ProfileController@password_update');", $routes);
        self::assertStringContainsString("'confirmed'", $method);
        self::assertStringContainsString('different:old_password', $method);
        self::assertStringContainsString('$user->currentAccessToken()', $method);
        self::assertStringContainsString('$otherTokens->delete();', $method);
    }

    public function test_email_notification_uses_declared_properties(): void
    {
        $notification = $this->source('app/Notifications/EmailNotification.php');
        self::assertStringContainsString('protected $subject;', $notification);
        self::assertStringContainsString('protected $email_body;', $notification);
    }
}
