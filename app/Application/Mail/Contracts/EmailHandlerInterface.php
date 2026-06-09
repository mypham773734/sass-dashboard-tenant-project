<?php

namespace App\Application\Mail\Contracts;

use App\Application\Mail\DTOs\EmailDTO;
use Carbon\Carbon;

interface EmailHandlerInterface
{
    public function handle(int $tenantId, array $context): EmailDTO;
    public function shouldSend(string $schedule, Carbon $now): bool;
}
