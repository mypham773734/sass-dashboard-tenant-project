<?php

namespace Tests\Feature\Notifications;

use App\Infrastructure\Notifications\Events\NotificationCreated;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastNotificationTest extends TestCase
{
    #[Test]
    public function notification_created_event_broadcasts_to_correct_channel(): void
    {
        $event = new NotificationCreated(
            notificationId: 42,
            userId: 5,
            tenantId: 3,
            title: 'Test notification',
            body: null,
            url: '/tasks/5',
            createdAt: Carbon::now(),
        );

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertStringContainsString('tenant.3.user.5', $channels[0]->name);
    }

    #[Test]
    public function notification_created_event_has_correct_payload(): void
    {
        $event = new NotificationCreated(
            notificationId: 42,
            userId: 5,
            tenantId: 3,
            title: 'Test notification',
            body: 'Body text',
            url: '/tasks/5',
            createdAt: Carbon::now(),
        );

        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('notification_id', $payload);
        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('body', $payload);
        $this->assertArrayHasKey('url', $payload);
        $this->assertArrayHasKey('created_at', $payload);

        $this->assertEquals(42, $payload['notification_id']);
        $this->assertEquals('Test notification', $payload['title']);
    }

    #[Test]
    public function notification_created_event_broadcasts_as_correct_event_name(): void
    {
        $event = new NotificationCreated(
            notificationId: 1,
            userId: 5,
            tenantId: 3,
            title: 'Test',
            body: null,
            url: null,
            createdAt: Carbon::now(),
        );

        $this->assertEquals('notification-created', $event->broadcastAs());
    }

    #[Test]
    public function notification_created_event_uses_notification_queue(): void
    {
        $event = new NotificationCreated(
            notificationId: 1,
            userId: 5,
            tenantId: 3,
            title: 'Test',
            body: null,
            url: null,
            createdAt: Carbon::now(),
        );

        $this->assertEquals(config('notification.queue', 'notifications'), $event->broadcastQueue());
    }
}
