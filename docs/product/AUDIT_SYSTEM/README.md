# Audit System — Entry Point

**Feature:** Audit logging — who did what, when, on which resource
**Status:** Approved — Approach D selected, ready to implement
**Last Updated:** 2026-06-06

---

## Problem

No mechanism exists to record user actions. When incidents occur:

- Cannot tell who deleted a task or project
- No timestamps for when data changed
- No evidence for user disputes
- Cannot meet compliance requirements (GDPR, SOC2)

## Solution

**Hybrid Audit Logger:** Use Cases inject `AuditLoggerInterface` and call `audit->log()` directly. Auth events use Laravel's built-in event system. Both write asynchronously via a dedicated Queue job.

```
CRUD Use Cases    → inject AuditLoggerInterface → audit->log()  → WriteAuditLogJob
Auth events       → Illuminate\Auth\Events\*    → AuthAuditListener → WriteAuditLogJob
Permission events → audit->log() at role assignment point
```

**Core principle:** Audit logs are **immutable** — append-only, never updated or deleted.

---

## Decision: Approach D (AuditLogger Service)

Four approaches were evaluated. **Approach D** was selected:

| | A: Package | B: Observer | C: Domain Events | D: AuditLogger ✅ |
|---|---|---|---|---|
| Clean Architecture | ❌ | ⚠️ | ✅ | ✅ |
| Auth/permission events | ❌ | ❌ | ✅ | ✅ |
| Scale (add new entity) | ✅ auto | ✅ auto | ❌ 4 files/entity | ✅ 1 line/entity |
| Boilerplate | Low | Low | High | Low |
| Testable | ❌ | ⚠️ | ✅ | ✅ (mock interface) |
| Seeder noise | ❌ | ❌ | ✅ | ✅ |

**Why not C (Domain Events)?**
Domain Events solve: "1 action → many side effects in different domains."
Audit log is 1 single side effect. Using Events adds indirection with no business value:

```
Approach C:  CreateTaskUseCase → TaskCreatedEvent → AuditEventListener → WriteAuditLogJob
Approach D:  CreateTaskUseCase → AuditLogger → WriteAuditLogJob
```

At scale: 10 entities × 4 CRUD = **40 Event classes + 40 handlers + 40 registrations** with Approach C.
Approach D: **1 line per Use Case, zero Event classes**.

When Domain Events become justified: if TaskCreatedEvent gains multiple listeners (`AuditListener` + `NotificationListener` + `WebhookListener`). For audit-only, Approach D is correct.

---

## Reading Order

1. **[01-REQUIREMENTS.md](./01-REQUIREMENTS.md)** — Event taxonomy, data structure, testable criteria
2. **[02-ARCHITECTURE.md](./02-ARCHITECTURE.md)** — System diagrams, class design, file structure
3. **[03-IMPLEMENTATION.md](./03-IMPLEMENTATION.md)** — Step-by-step code, 5 phases, 4 days

---

## Quick Reference

### Event naming convention

```
{entity}.{past_tense_verb}

task.created     task.updated     task.deleted
task.status_changed              task.assigned
project.created  project.updated  project.deleted
tenant.updated   tenant.user_invited  tenant.user_removed  tenant.user_role_changed
permission.role_assigned         permission.role_revoked
auth.login       auth.logout      auth.login_failed
```

### Core API — how to add audit logging to a Use Case

```php
class CreateTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $repo,
        private AuditLoggerInterface    $audit,  // inject
    ) {}

    public function execute(CreateTaskDTO $dto, int $tenantId, int $userId): TaskEntity
    {
        $task = $this->repo->create($dto, $tenantId, $userId);

        $this->audit->log(
            action:     'task.created',
            entityId:   $task->id,
            entityType: 'Task',
            newValues:  ['title' => $task->title, 'status' => $task->status],
        );

        return $task;
    }
}
```

### Permission dependency

The audit viewer requires permission `audit:view` (Owner + Admin).
**Add `audit:view` to the RBAC seeder** — see [PERMISSION_RBAC/01-REQUIREMENTS.md](../PERMISSION_RBAC/01-REQUIREMENTS.md).

### Config

```env
AUDIT_ENABLED=true           # set false in local dev / tests
AUDIT_RETENTION_DAYS=90      # records older than this are cleaned up daily
```

---

## Timeline

| Phase | Work | Duration |
|---|---|---|
| 1 | DB migration + Domain Entity + Repository + Queue Job | Day 1 morning |
| 2 | AuditLoggerInterface + QueuedAuditLogger + NullAuditLogger + bindings | Day 1 afternoon |
| 3 | Inject into Task/Project Use Cases + AuthAuditListener | Day 2 |
| 4 | GetAuditLogsUseCase + AuditController + Blade view | Day 3 |
| 5 | Feature tests (13+ cases) | Day 4 morning |
| **Total** | | **4 days** |

---

## Success Criteria

- [ ] Every Create/Update/Delete on Task, Project, Tenant generates an audit log
- [ ] Every login / logout / failed login is recorded
- [ ] Every role assignment/change is recorded
- [ ] Audit logs are never deleted via application code (immutable)
- [ ] Audit viewer shows correct timeline, filterable by user/action/date
- [ ] Cross-tenant isolation: Owner only sees their own tenant's logs
- [ ] Writing an audit log adds < 5ms to the HTTP request (async queue)
- [ ] Tests cover all 13 action types
