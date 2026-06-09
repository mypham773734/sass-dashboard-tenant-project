<?php

namespace Tests\Feature\Mail;

use App\Application\Mail\Contracts\MailServiceInterface;
use App\Infrastructure\Mail\Jobs\SendMailJob;
use App\Infrastructure\Mail\NullMailService;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendMailJobTest extends TestCase
{
    #[Test]
    public function handle_delegates_to_mail_service_send(): void
    {
        $mailService = new NullMailService();

        $job = new SendMailJob('user_invitation', 1, ['foo' => 'bar']);
        $job->handle($mailService);

        $this->assertTrue($mailService->assertSent('user_invitation'));

        $sent = $mailService->getSent();
        $this->assertSame(1,             $sent[0]['tenantId']);
        $this->assertSame(['foo' => 'bar'], $sent[0]['context']);
    }

    #[Test]
    public function handle_passes_context_to_service(): void
    {
        $mailService = new NullMailService();
        $context     = ['invited_email' => 'bob@example.com', 'tenant_name' => 'Acme'];

        $job = new SendMailJob('user_invitation', 5, $context);
        $job->handle($mailService);

        $sent = $mailService->getSent();
        $this->assertSame($context, $sent[0]['context']);
    }

    #[Test]
    public function failed_logs_error_with_type_and_tenant(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'SendMailJob failed'
                    && $context['type']      === 'audit_digest'
                    && $context['tenant_id'] === 3;
            });

        $job = new SendMailJob('audit_digest', 3);
        $job->failed(new \RuntimeException('SMTP connection refused'));
    }

    #[Test]
    public function job_has_correct_retry_settings(): void
    {
        $job = new SendMailJob('user_invitation', 1);

        $this->assertSame(3,  $job->tries);
        $this->assertSame(60, $job->backoff);
    }
}
