<?php

namespace App\Infrastructure\Mail\Jobs;

use App\Application\Mail\Contracts\MailServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMailJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $type,
        public readonly int    $tenantId,
        public readonly array  $context = [],
    ) {}

    public function handle(MailServiceInterface $mailService): void
    {
        $mailService->send($this->type, $this->tenantId, $this->context);
    }

    public function failed(\Throwable $e): void
    {
        \Illuminate\Support\Facades\Log::error('SendMailJob failed', [
            'type'      => $this->type,
            'tenant_id' => $this->tenantId,
            'error'     => $e->getMessage(),
        ]);
    }
}
