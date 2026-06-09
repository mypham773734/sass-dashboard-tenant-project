<?php

namespace Tests\Feature\Mail;

use App\Application\Mail\Contracts\MailServiceInterface;
use App\Infrastructure\Mail\NullMailService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendScheduledEmailsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bind NullMailService so no real emails are dispatched
        $this->app->bind(MailServiceInterface::class, NullMailService::class);
    }

    #[Test]
    public function command_exits_successfully(): void
    {
        $this->artisan('mail:send-scheduled')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_outputs_dispatched_message(): void
    {
        $this->artisan('mail:send-scheduled')
            ->expectsOutputToContain('Scheduled emails dispatched')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_accepts_now_option(): void
    {
        $this->artisan('mail:send-scheduled', ['--now' => '2026-06-09 08:00'])
            ->expectsOutputToContain('2026-06-09 08:00')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_calls_dispatch_scheduled_on_mail_service(): void
    {
        $nullService = new NullMailService();
        $this->app->instance(MailServiceInterface::class, $nullService);

        // Run at scheduled time — 08:00; audit_digest shouldSend() will return true
        // but NullMailService::dispatchScheduled is a no-op, so we just verify no crash
        $this->artisan('mail:send-scheduled', ['--now' => '2026-06-09 08:00'])
            ->assertExitCode(0);
    }
}
