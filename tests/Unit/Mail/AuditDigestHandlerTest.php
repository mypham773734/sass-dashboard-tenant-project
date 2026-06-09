<?php

namespace Tests\Unit\Mail;

use App\Domain\Audit\Entities\AuditLog;
use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use App\Infrastructure\Mail\Handlers\AuditDigestHandler;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditDigestHandlerTest extends TestCase
{
    use RefreshDatabase;

    private AuditDigestHandler $handler;
    private Tenant $tenant;
    private User   $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::withoutGlobalScopes()->create([
            'name'      => 'Acme Corp',
            'slug'      => 'acme-corp',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['name' => 'Alice']);

        // Attach to tenant with role column required by tenant_user pivot
        $this->admin->tenants()->attach($this->tenant->id, ['role' => 'admin']);

        // Assign Spatie role so isAdminOfTenant() returns true
        $role = Role::firstOrCreate(
            ['name' => 'admin', 'tenant_id' => $this->tenant->id],
            ['guard_name' => 'web'],
        );
        $this->admin->assignRole($role);

        $auditRepo = $this->createMock(AuditRepositoryInterface::class);
        $auditRepo->method('getRecentByTenant')->willReturn([
            new AuditLog(
                id: 1, tenantId: $this->tenant->id, userId: $this->admin->id,
                action: 'project.created', entityType: 'Project', entityId: 10,
                oldValues: null, newValues: null, ipAddress: null, userAgent: null,
                metadata: null, createdAt: now()->toDateTimeString(),
            ),
            new AuditLog(
                id: 2, tenantId: $this->tenant->id, userId: $this->admin->id,
                action: 'task.updated', entityType: 'Task', entityId: 5,
                oldValues: null, newValues: null, ipAddress: null, userAgent: null,
                metadata: null, createdAt: now()->subMinutes(10)->toDateTimeString(),
            ),
        ]);

        $this->handler = new AuditDigestHandler($auditRepo);
    }

    #[Test]
    public function handle_returns_email_dto_with_correct_type(): void
    {
        $dto = $this->handler->handle($this->tenant->id);

        $this->assertSame('audit_digest', $dto->type);
    }

    #[Test]
    public function handle_subject_contains_tenant_name_and_period(): void
    {
        $dto = $this->handler->handle($this->tenant->id);

        $this->assertStringContainsString('Acme Corp',        $dto->subject);
        $this->assertStringContainsString('Daily Audit Digest', $dto->subject);
    }

    #[Test]
    public function handle_uses_admin_as_recipient(): void
    {
        $dto = $this->handler->handle($this->tenant->id);

        $this->assertContains($this->admin->email, $dto->recipients);
    }

    #[Test]
    public function handle_passes_logs_in_data(): void
    {
        $dto = $this->handler->handle($this->tenant->id);

        $this->assertCount(2, $dto->data['logs']);
        $this->assertSame(2,  $dto->data['totalEvents']);
    }

    #[Test]
    public function handle_builds_summary_grouped_by_action_prefix(): void
    {
        $dto = $this->handler->handle($this->tenant->id);

        $this->assertArrayHasKey('project', $dto->data['summary']);
        $this->assertArrayHasKey('task',    $dto->data['summary']);
        $this->assertSame(1, $dto->data['summary']['project']);
        $this->assertSame(1, $dto->data['summary']['task']);
    }

    #[Test]
    public function handle_throws_when_no_admin_found(): void
    {
        $emptyTenant = Tenant::withoutGlobalScopes()->create([
            'name' => 'Empty', 'slug' => 'empty-' . rand(), 'is_active' => true,
        ]);

        $auditRepo = $this->createMock(AuditRepositoryInterface::class);
        $auditRepo->method('getRecentByTenant')->willReturn([]);

        $handler = new AuditDigestHandler($auditRepo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/no admin\/owner found/');

        $handler->handle($emptyTenant->id);
    }

    #[Test]
    public function should_send_returns_true_at_08_00_for_daily_schedule(): void
    {
        $at0800 = Carbon::createFromFormat('H:i', '08:00');
        $this->assertTrue($this->handler->shouldSend('daily_08_00', $at0800));
    }

    #[Test]
    public function should_send_returns_false_outside_scheduled_time(): void
    {
        $at0900 = Carbon::createFromFormat('H:i', '09:00');
        $this->assertFalse($this->handler->shouldSend('daily_08_00', $at0900));
    }

    #[Test]
    public function should_send_returns_false_for_unknown_schedule(): void
    {
        $this->assertFalse($this->handler->shouldSend('weekly_monday', Carbon::now()));
    }
}
