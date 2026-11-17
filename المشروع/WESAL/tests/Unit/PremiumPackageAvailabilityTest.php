<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PremiumPackageAvailabilityTest extends TestCase
{
    private function methodBody(string $source, string $start, string $end): string
    {
        $startAt = strpos($source, $start);
        self::assertNotFalse($startAt, 'Method start not found: ' . $start);
        $endAt = strpos($source, $end, $startAt + strlen($start));
        self::assertNotFalse($endAt, 'Method end not found: ' . $end);

        return substr($source, $startAt, $endAt - $startAt);
    }

    public function test_disabling_a_package_does_not_revoke_a_paid_membership_before_expiry(): void
    {
        $path = __DIR__ . '/../../app/Utility/SubscriptionUtility.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $currentPackage = $this->methodBody(
            $source,
            'public static function currentActivePaidPackageForMember(',
            'public static function currentActivePaidPackage('
        );
        $paidScope = $this->methodBody(
            $source,
            'public static function constrainCurrentPaidMembers(',
            'public static function constrainNonPaidMembers('
        );
        $nonPaidScope = $this->methodBody(
            $source,
            'public static function constrainNonPaidMembers(',
            'public static function currentActivePaidPackageForMember('
        );

        foreach ([$currentPackage, $paidScope, $nonPaidScope] as $method) {
            self::assertStringNotContainsString("where('packages.active', 1)", $method);
            self::assertStringContainsString("where('packages.price', '>', 0)", $method);
            self::assertStringContainsString("where('packages.validity', '>', 0)", $method);
        }

        self::assertStringContainsString('whereDate(\'package_validity\', \'>=\', $today)', $paidScope);
        self::assertStringContainsString('whereDate(\'package_validity\', \'>=\', $today)', $nonPaidScope);
    }

    public function test_new_purchases_and_activations_still_require_an_active_package(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/Api/PackageController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $purchase = $this->methodBody(
            $source,
            'public function package_purchase(Request $request)',
            'private function resolveActivationKey('
        );
        $activationStart = strpos($source, 'public function package_payment_done(');
        self::assertNotFalse($activationStart);
        $activation = substr($source, $activationStart);

        self::assertStringContainsString("->where('active', 1)", $purchase);
        self::assertStringContainsString("->where('active', 1)", $activation);
    }

    public function test_explicit_admin_cancellation_still_clears_subscription_entitlements(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/MemberController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $cancel = $this->methodBody(
            $source,
            'public function package_cancel(Request $request, $id)',
            'public function package_do_update('
        );

        self::assertStringContainsString('SubscriptionUtility::cancelSubscription(', $cancel);
        self::assertStringContainsString('all paid entitlements were removed', $cancel);
    }

    public function test_base_package_cannot_be_deleted_through_a_direct_admin_request(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/PackageController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $destroy = $this->methodBody(
            $source,
            'public function destroy($id)',
            'private function presentationRules('
        );

        self::assertStringContainsString('(int) $package->id === 1', $destroy);
        self::assertStringContainsString('لا يمكن حذف الباقة الأساسية', $destroy);
    }
}
