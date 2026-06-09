<?php

namespace App\Infrastructure\Mail;

use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use App\Application\Audit\AuditLoggerInterface;
use App\Application\Mail\Contracts\{
    MailServiceInterface, 
    EmailHandlerInterface
};
use App\Application\Mail\DTOs\EmailDTO;
use App\Infrastructure\Mail\Jobs\SendMailJob;
use App\Models\Tenant; 

class MailService implements MailServiceInterface
{
    public function __construct(
        private AuditLoggerInterface $auditLogger
    ) {}
    public function dispatch(string $type, int $tenantId, array $context = []): void
    {
        if (! config('mail-service.enabled', true)) {
            return;
        }

        $this->assertTypeExists($type);

        if (! $this->isTypeEnabled($type)) {
            return;
        }

        SendMailJob::dispatch($type, $tenantId, $context)
            ->onQueue(config('mail-service.queue', 'mail'));
    }

    public function send(string $type, int $tenantId, array $context = []): void
    {
        if (! config('mail-service.enabled', true)) {
            return;
        }

        $this->assertTypeExists($type);

        if (! $this->isTypeEnabled($type)) {
            return;
        }

        $dto      = $this->resolveHandler($type)->handle($tenantId, $context);
        $mailable = $this->resolveMailable($dto);

        Mail::to($dto->recipients)->send($mailable);

        $this->auditLogger->log(
            action: 'mail.sent',
            entityType: 'Mail',
            metadata: [
                'type'       => $dto->type,
                'recipients' => $dto->recipients,
                'tenant_id'  => $tenantId,
            ],
        );
    }

    public function dispatchScheduled(Carbon $now): void
    {
        foreach (config('mail-service.email_types', []) as $type => $typeConfig) {
            if (empty($typeConfig['schedule']) || ! $this->isTypeEnabled($type)) {
                continue;
            }

            $handler = $this->resolveHandler($type);

            if (! $handler->shouldSend($typeConfig['schedule'], $now)) {
                continue;
            }

            Tenant::where('is_active', true)
                ->each(fn($tenant) => $this->dispatch($type, $tenant->id));
        }
    }

    private function resolveHandler(string $type): EmailHandlerInterface
    {
        $class = config("mail-service.email_types.{$type}.handler")
            ?? throw new \InvalidArgumentException("No handler configured for mail type: [{$type}]");

        return app($class);
    }

    private function resolveMailable(EmailDTO $dto): Mailable
    {
        $class = config("mail-service.email_types.{$dto->type}.mailable")
            ?? throw new \InvalidArgumentException("No mailable configured for mail type: [{$dto->type}]");

        return new $class($dto);
    }

    private function assertTypeExists(string $type): void
    {
        if (! config("mail-service.email_types.{$type}")) {
            throw new \InvalidArgumentException("Unknown mail type: [{$type}]");
        }
    }

    private function isTypeEnabled(string $type): bool
    {
        return (bool) config("mail-service.email_types.{$type}.enabled", true);
    }
}
