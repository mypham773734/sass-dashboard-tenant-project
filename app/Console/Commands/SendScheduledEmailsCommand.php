<?php

namespace App\Console\Commands;

use App\Application\Mail\Contracts\MailServiceInterface;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendScheduledEmailsCommand extends Command
{
    protected $signature   = 'mail:send-scheduled {--now= : Override current time (Y-m-d H:i)}';
    protected $description = 'Dispatch scheduled emails (run every minute via scheduler)';

    public function handle(MailServiceInterface $mailService): int
    {
        $now = $this->option('now')
            ? Carbon::createFromFormat('Y-m-d H:i', $this->option('now'))
            : Carbon::now();

        $mailService->dispatchScheduled($now);

        $this->info("Scheduled emails dispatched for {$now->format('Y-m-d H:i')}.");

        return self::SUCCESS;
    }
}
