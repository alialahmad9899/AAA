<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SubscriptionPaymentContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    private function methodBody(string $source, string $start, string $end): string
    {
        $startAt = strpos($source, $start);
        self::assertNotFalse($startAt, 'Method start not found: ' . $start);
        $endAt = strpos($source, $end, $startAt + strlen($start));
        self::assertNotFalse($endAt, 'Method end not found: ' . $end);

        return substr($source, $startAt, $endAt - $startAt);
    }

    public function test_sham_cash_uses_the_heading_saved_by_the_admin_manual_payment_form(): void
    {
        $model = $this->source('app/Models/ManualPaymentMethod.php');
        $packageController = $this->source('app/Http/Controllers/Api/PackageController.php');
        $paymentTypesController = $this->source('app/Http/Controllers/Api/Payment/PaymentTypesController.php');
        $adminController = $this->source('app/Http/Controllers/ManualPaymentMethodController.php');
        $adminForm = $this->source('resources/views/admin/manual_payment_methods/create.blade.php');

        self::assertStringContainsString("function isShamCash(): bool", $model);
        self::assertStringContainsString("$"."this->heading", $model);
        self::assertStringContainsString("$"."manual_payment_method->heading = $"."request->heading", $adminController);
        self::assertStringContainsString('name="heading"', $adminForm);

        $listMethod = $this->methodBody(
            $packageController,
            'public function manual_payment_methods(Request $request)',
            'public function active_packages(Request $request)'
        );
        $purchaseMethod = $this->methodBody(
            $packageController,
            'public function package_purchase(Request $request)',
            'private function resolveActivationKey('
        );

        self::assertStringContainsString('$method->isShamCash()', $listMethod);
        self::assertStringNotContainsString('heading_en', $listMethod);
        self::assertStringContainsString('$manualMethod->isShamCash()', $purchaseMethod);
        self::assertStringNotContainsString('heading_en', $purchaseMethod);
        self::assertStringContainsString('$method->isShamCash()', $paymentTypesController);
        self::assertStringNotContainsString('heading_en', $paymentTypesController);
    }

    public function test_package_payment_activation_key_has_a_safe_repair_migration(): void
    {
        $migration = $this->source('database/migrations/2026_11_20_000001_ensure_package_payment_activation_key.php');

        self::assertStringContainsString("Schema::hasTable('package_payments')", $migration);
        self::assertStringContainsString("Schema::hasColumn('package_payments', 'activation_key')", $migration);
        self::assertStringContainsString("$"."table->string('activation_key', 64)->nullable()->after('payment_method')", $migration);
        self::assertStringContainsString("$"."table->unique('activation_key', 'package_payments_activation_key_unique')", $migration);
        self::assertStringContainsString("SHOW INDEX FROM", $migration);
        self::assertStringContainsString('hasSingleColumnUniqueIndex', $migration);
        self::assertStringContainsString('Intentionally non-destructive', $migration);
    }

    public function test_stripe_activation_is_read_from_the_current_database_setting(): void
    {
        $stripeController = $this->source('app/Http/Controllers/Api/Payment/StripeController.php');
        $paymentTypesController = $this->source('app/Http/Controllers/Api/Payment/PaymentTypesController.php');
        $adminSettings = $this->source('app/Http/Controllers/SettingController.php');
        $settingsView = $this->source('resources/views/admin/settings/payment_method_settings.blade.php');

        $stripeCreate = $this->methodBody(
            $stripeController,
            'public function create_payment_intent(Request $request)',
            'public function confirm_payment_intent(Request $request)'
        );

        self::assertStringContainsString("where('type', 'stripe_payment_activation')", $stripeCreate);
        self::assertStringNotContainsString("get_setting('stripe_payment_activation')", $stripeCreate);
        self::assertStringContainsString("'stripe_payment_activation'", $paymentTypesController);
        self::assertStringContainsString("Setting::query()", $paymentTypesController);
        self::assertStringContainsString("$"."payemnt_activation->value = 1", $adminSettings);
        self::assertStringContainsString('name="stripe_payment_activation"', $settingsView);
        self::assertStringContainsString("Log::warning('Stripe PaymentIntent creation blocked", $stripeCreate);
    }
}
