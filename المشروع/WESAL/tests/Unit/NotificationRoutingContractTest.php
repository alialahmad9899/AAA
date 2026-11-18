<?php

namespace Tests\Unit;

use App\Notifications\DbStoreNotification;
use PHPUnit\Framework\TestCase;

class NotificationRoutingContractTest extends TestCase
{
    public function test_notification_database_payload_keeps_entity_and_actor_routing_ids(): void
    {
        $notification = new DbStoreNotification(
            'profile_view',
            'notification-contract-1',
            73,
            73,
            'A member viewed your profile.',
            'wesal://member/73',
            'Profile viewed'
        );

        $payload = $notification->toDatabase(new \stdClass());

        $this->assertSame('profile_view', $payload['type']);
        $this->assertSame('notification-contract-1', $payload['id']);
        $this->assertSame(73, $payload['notify_by']);
        $this->assertSame(73, $payload['info_id']);
        $this->assertSame('wesal://member/73', $payload['route']);
        $this->assertSame('Profile viewed', $payload['title']);
    }

    public function test_package_invoice_notification_contract_keeps_payment_id(): void
    {
        $notification = new DbStoreNotification(
            'package',
            'notification-contract-2',
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
}
