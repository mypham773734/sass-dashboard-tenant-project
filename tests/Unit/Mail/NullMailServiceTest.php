<?php

namespace Tests\Unit\Mail;

use App\Infrastructure\Mail\NullMailService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NullMailServiceTest extends TestCase
{
    private NullMailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NullMailService();
    }

    #[Test]
    public function dispatch_records_sent_mail(): void
    {
        $this->service->dispatch('user_invitation', 1, ['foo' => 'bar']);

        $this->assertTrue($this->service->assertSent('user_invitation'));
    }

    #[Test]
    public function send_records_sent_mail(): void
    {
        $this->service->send('tenant_notification', 2);

        $this->assertTrue($this->service->assertSent('tenant_notification'));
    }

    #[Test]
    public function assert_not_sent_returns_true_when_nothing_sent(): void
    {
        $this->assertTrue($this->service->assertNotSent('user_invitation'));
    }

    #[Test]
    public function assert_not_sent_returns_false_after_dispatch(): void
    {
        $this->service->dispatch('user_invitation', 1);

        $this->assertFalse($this->service->assertNotSent('user_invitation'));
    }

    #[Test]
    public function reset_clears_all_recorded_mails(): void
    {
        $this->service->dispatch('user_invitation', 1);
        $this->service->reset();

        $this->assertTrue($this->service->assertNotSent('user_invitation'));
        $this->assertEmpty($this->service->getSent());
    }

    #[Test]
    public function get_sent_returns_all_recorded_entries(): void
    {
        $this->service->dispatch('user_invitation',     1, ['a' => 1]);
        $this->service->dispatch('tenant_notification', 2, ['b' => 2]);

        $sent = $this->service->getSent();

        $this->assertCount(2, $sent);
        $this->assertSame('user_invitation',     $sent[0]['type']);
        $this->assertSame('tenant_notification', $sent[1]['type']);
        $this->assertSame(1, $sent[0]['tenantId']);
    }

    #[Test]
    public function dispatch_scheduled_does_nothing(): void
    {
        $this->service->dispatchScheduled(Carbon::now());

        $this->assertEmpty($this->service->getSent());
    }
}
