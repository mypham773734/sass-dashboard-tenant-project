<?php

namespace App\Application\Mail\Contracts;

use Carbon\Carbon;

interface MailServiceInterface
{
    public function dispatch(string $type, int $tenantId, array $context = []): void;
    public function send(string $type, int $tenantId, array $context = []): void;
    public function dispatchScheduled(Carbon $now): void;
}
