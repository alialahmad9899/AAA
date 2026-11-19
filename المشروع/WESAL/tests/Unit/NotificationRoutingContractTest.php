<?php

namespace Illuminate\Bus {
    if (!trait_exists(Queueable::class)) {
        trait Queueable
        {
        }
    }
}

namespace Illuminate\Notifications {
    if (!class_exists(Notification::class)) {
        class Notification
        {
        }
    }
}

namespace Tests\Unit {
    use App\Notifications\DbStoreNotification;
    use PHPUnit\Framework\TestCase;

    require_once dirname(__DIR__, 2) . '/app/Notifications/DbStoreNotification.php';

    class NotificationRoutingContractTest extends TestCase
    {
        public function test_profile_view_notification_keeps_viewer_id_and_deep_link(): void
        {
            $notification = new DbStoreNotification(
                'profile_view',
                'notification-contract-profile-1',
                73,
                73,
                'A member viewed your profile.',
                'wesal://member/73',
                'Profile viewed'
            );

            $payload = $notification->toDatabase(new \stdClass());

            $this->assertSame('profile_view', $payload['type']);
            $this->assertSame('notification-contract-profile-1', $payload['id']);
            $this->assertSame(73, $payload['notify_by']);
            $this->assertSame(73, $payload['info_id']);
            $this->assertSame('wesal://member/73', $payload['route']);
            $this->assertSame('Profile viewed', $payload['title']);
        }

        public function test_interest_notification_keeps_actor_and_interest_ids(): void
        {
            $notification = new DbStoreNotification(
                'express_interest',
                'notification-contract-interest-1',
                84,
                602,
                'A member expressed interest in you.',
                'wesal://member/84',
                'New interest'
            );

            $payload = $notification->toDatabase(new \stdClass());

            $this->assertSame('express_interest', $payload['type']);
            $this->assertSame(84, $payload['notify_by']);
            $this->assertSame(602, $payload['info_id']);
            $this->assertSame('wesal://member/84', $payload['route']);
        }

        public function test_package_invoice_notification_keeps_payment_id_and_invoice_route(): void
        {
            $notification = new DbStoreNotification(
                'package',
                'notification-contract-package-1',
                null,
                501,
                'Your package was activated.',
                'wesal://package-invoice/501',
                'Package activation'
            );

            $payload = $notification->toDatabase(new \stdClass());

            $this->assertSame('package', $payload['type']);
            $this->assertSame(501, $payload['info_id']);
            $this->assertSame('wesal://package-invoice/501', $payload['route']);
        }

        public function test_package_invoice_lookup_keeps_soft_deleted_owner_invoices_without_weakening_ownership(): void
        {
            $controllerPath = dirname(__DIR__, 2)
                . '/app/Http/Controllers/Api/PackageController.php';
            $source = file_get_contents($controllerPath);

            $this->assertIsString($source);
            $this->assertStringContainsString('PackagePayment::withTrashed()', $source);
            $this->assertStringContainsString(
                "->where('user_id', auth()->id())",
                $source
            );
            $this->assertStringContainsString(
                '->whereKey((int) $paymentId)',
                $source
            );
            $this->assertStringContainsString('payment_was_soft_deleted', $source);
        }

        public function test_notification_serialization_is_database_only_and_keeps_route(): void
        {
            $notification = new DbStoreNotification(
                'support_ticket',
                'notification-contract-support-1',
                11,
                909,
                'Your support ticket was updated.',
                'wesal://support-ticket/909',
                'Support update'
            );

            $this->assertSame(['database'], $notification->via(new \stdClass()));
            $this->assertSame(
                $notification->toDatabase(new \stdClass()),
                $notification->toArray(new \stdClass())
            );
        }
    }
}
