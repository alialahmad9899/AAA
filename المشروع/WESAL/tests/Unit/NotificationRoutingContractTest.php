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

        public function test_package_invoice_resource_keeps_the_fields_consumed_by_flutter(): void
        {
            $root = dirname(__DIR__, 2);
            $resource = file_get_contents(
                $root . '/app/Http/Resources/PackagePaymentInvoiceResource.php'
            );
            $paymentModel = file_get_contents(
                $root . '/app/Models/PackagePayment.php'
            );

            $this->assertIsString($resource);
            $this->assertIsString($paymentModel);
            foreach ([
                "'payment_id'",
                "'purchase_code'",
                "'purchase_date'",
                "'payment_method'",
                "'payment_status'",
                "'package_name'",
                "'total'",
            ] as $field) {
                $this->assertStringContainsString($field, $resource);
            }
            $this->assertStringContainsString(
                'belongsTo(User::class)->withTrashed()',
                $paymentModel
            );
            $this->assertStringContainsString(
                'belongsTo(Package::class)->withTrashed()',
                $paymentModel
            );
        }

        public function test_fcm_android_tray_action_and_versioned_channels_match_flutter(): void
        {
            $root = dirname(__DIR__, 2);
            $firebaseService = file_get_contents(
                $root . '/app/Services/FirebaseNotificationService.php'
            );
            $repositoryRoot = dirname($root, 2);
            $flutterService = file_get_contents(
                $repositoryRoot . '/فلاتر/lib/services/notification_service.dart'
            );
            $manifest = file_get_contents(
                $repositoryRoot . '/فلاتر/android/app/src/main/AndroidManifest.xml'
            );

            $this->assertIsString($firebaseService);
            $this->assertIsString($flutterService);
            $this->assertIsString($manifest);
            $this->assertStringContainsString(
                '"click_action" => "FLUTTER_NOTIFICATION_CLICK"',
                $firebaseService
            );
            $this->assertStringContainsString(
                'wesal_high_importance_channel_v2',
                $firebaseService
            );
            $this->assertStringContainsString(
                'wesal_chat_alert_channel_v2',
                $firebaseService
            );
            $this->assertStringContainsString(
                "'wesal_high_importance_channel_v2'",
                $flutterService
            );
            $this->assertStringContainsString(
                "'wesal_chat_alert_channel_v2'",
                $flutterService
            );
            $this->assertStringContainsString(
                'android:value="wesal_high_importance_channel_v2"',
                $manifest
            );
        }

        public function test_manual_package_approval_notification_includes_an_explicit_invoice_route(): void
        {
            $controllerPath = dirname(__DIR__, 2)
                . '/app/Http/Controllers/PackagePaymentController.php';
            $source = file_get_contents($controllerPath);

            $this->assertIsString($source);
            $this->assertStringContainsString(
                "'wesal://package-invoice/' . $result['payment']->id",
                $source
            );
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
