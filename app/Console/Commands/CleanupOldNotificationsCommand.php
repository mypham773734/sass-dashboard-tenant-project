<?php

namespace App\Console\Commands;

use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use Illuminate\Console\Command;
use App\Models\Tenant; 

class CleanupOldNotificationsCommand extends Command
{
    protected $signature = 'notification:cleanup {--days=30}';

    protected $description = 'Delete notifications older than specified days';

    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $before = now()->subDays($days);

        $this->info("Deleting notifications older than {$days} days (before {$before->toDateString()})...");

        try {
            // Delete from all tenants
            $totalDeleted = 0;
            $tenants = Tenant::withoutGlobalScopes()->pluck('id');

            foreach ($tenants as $tenantId) {
                $deleted = $this->notificationRepository->deleteOlderThan($tenantId, $before);
                $totalDeleted += $deleted;
            }

            $this->info("✓ Deleted {$totalDeleted} old notifications");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
