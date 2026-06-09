<?php

namespace App\Infrastructure\Mail;

use App\Application\Mail\Contracts\MailServiceInterface;
use Carbon\Carbon;

class NullMailService implements MailServiceInterface
{
    private array $sent = [];

    public function dispatch(string $type, int $tenantId, array $context = []): void
    {
        $this->sent[] = compact('type', 'tenantId', 'context');
    }

    public function send(string $type, int $tenantId, array $context = []): void
    {
        $this->sent[] = compact('type', 'tenantId', 'context');
    }

    public function dispatchScheduled(Carbon $now): void {}

    public function assertSent(string $type): bool
    {
        return collect($this->sent)->contains('type', $type);
    }

    public function assertNotSent(string $type): bool
    {
        return ! $this->assertSent($type);
    }

    public function getSent(): array
    {
        return $this->sent;
    }

    public function reset(): void
    {
        $this->sent = [];
    }
}
