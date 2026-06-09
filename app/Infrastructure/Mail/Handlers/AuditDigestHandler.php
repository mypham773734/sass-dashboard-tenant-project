<?php

namespace App\Infrastructure\Mail\Handlers;

use App\Application\Mail\Contracts\EmailHandlerInterface;
use App\Application\Mail\DTOs\EmailDTO;
use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

class AuditDigestHandler implements EmailHandlerInterface
{
    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository,
    ) {}

    public function handle(int $tenantId, array $context = []): EmailDTO
    {
        $tenant = Tenant::findOrFail($tenantId);

        $since  = $context['since'] ?? Carbon::now()->subDay()->startOfDay();
        $until  = $context['until'] ?? Carbon::now()->subDay()->endOfDay();
        $period = Carbon::parse($since)->format('M d, Y');

        $logs = $this->auditRepository->getRecentByTenant($tenantId, Carbon::parse($since));

        $recipients = $this->resolveAdminEmails($tenantId);

        if (empty($recipients)) {
            throw new \DomainException(
                "AuditDigestHandler: no admin/owner found for tenant [{$tenantId}]"
            );
        }

        $summary = $this->buildSummary($logs);

        return new EmailDTO(
            type:       'audit_digest',
            subject:    "[{$tenant->name}] Daily Audit Digest — {$period}",
            recipients: $recipients,
            template:   'emails.audit-digest',
            data:       [
                'tenantName'  => $tenant->name,
                'period'      => $period,
                'since'       => Carbon::parse($since)->toDateTimeString(),
                'until'       => Carbon::parse($until)->toDateTimeString(),
                'totalEvents' => count($logs),
                'logs'        => array_slice($logs, 0, 50),
                'summary'     => $summary,
            ],
        );
    }

    public function shouldSend(string $schedule, Carbon $now): bool
    {
        return match ($schedule) {
            'daily_08_00' => $now->format('H:i') === '08:00',
            default       => false,
        };
    }

    private function resolveAdminEmails(int $tenantId): array
    {
        return User::whereHas('tenants', fn($q) => $q->where('tenants.id', $tenantId))
            ->get()
            ->filter(fn($user) => $user->isAdminOfTenant($tenantId))
            ->pluck('email')
            ->toArray();
    }

    /** Group logs by action prefix for the summary table. */
    private function buildSummary(array $logs): array
    {
        $counts = [];

        foreach ($logs as $log) {
            $prefix = explode('.', $log->action)[0];
            $counts[$prefix] = ($counts[$prefix] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }
}
