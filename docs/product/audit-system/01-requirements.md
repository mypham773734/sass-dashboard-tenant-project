# Audit System — Requirements

**Status:** Approved
**Last Updated:** 2026-06-06

---

## Event Taxonomy (Canonical)

This is the single source of truth for all auditable events.

### Auth Events

| Event Key | Trigger | Required Fields |
|---|---|---|
| `auth.login` | Successful login | user_id, ip_address, user_agent |
| `auth.logout` | User logout | user_id, ip_address |
| `auth.login_failed` | Wrong password | ip_address, metadata.email_attempted |

> Auth events have `tenant_id = null` — they are not scoped to a tenant.

### Task Events

| Event Key | Trigger | old_values | new_values |
|---|---|---|---|
| `task.created` | New task created | null | title, status, priority, project_id, assignee_id |
| `task.updated` | Task fields changed | title, status, priority | title, status, priority |
| `task.deleted` | Task deleted | title, status (snapshot) | null |
| `task.status_changed` | Status field changed | status | status |
| `task.assigned` | Assignee changed | null | assigned_to (user_id) |

### Project Events

| Event Key | Trigger | old_values | new_values |
|---|---|---|---|
| `project.created` | New project created | null | name, description |
| `project.updated` | Project fields changed | name | name |
| `project.deleted` | Project deleted | name (snapshot) | null |

### Tenant Events

| Event Key | Trigger | Required Fields |
|---|---|---|
| `tenant.updated` | Tenant settings changed | old_values, new_values |
| `tenant.user_invited` | User invited to tenant | metadata.invited_email, metadata.role |
| `tenant.user_removed` | User removed from tenant | entity_id (removed user_id) |
| `tenant.user_role_changed` | User's role changed | old_values.role, new_values.role |

### Permission Events

| Event Key | Trigger | Required Fields |
|---|---|---|
| `permission.role_assigned` | Role assigned to user | entity_id (user_id), new_values.role_name |
| `permission.role_revoked` | Role revoked from user | entity_id (user_id), old_values.role_name |

---

## Data Structure

Every audit log record:

```
audit_logs
├── id            BIGINT   — Primary key, auto-increment
├── tenant_id     INT      — Nullable (null = auth events, system-level)
├── user_id       INT      — Nullable (null = system action)
├── action        VARCHAR  — "task.created", "auth.login" — never null
├── entity_type   VARCHAR  — "Task", "Project", "Tenant", "User" — nullable
├── entity_id     BIGINT   — ID of affected resource — nullable
├── old_values    JSON     — State before change — null for creates
├── new_values    JSON     — State after change — null for deletes
├── ip_address    VARCHAR  — Nullable
├── user_agent    TEXT     — Nullable
├── metadata      JSON     — Extra context (e.g., email on login_failed) — nullable
└── created_at    TIMESTAMP — Immutable, no updated_at column
```

**Rules:**
- No `updated_at` — audit logs are never edited
- `old_values` / `new_values` record only **important fields** — never dump entire model
- Sensitive fields (password, token, secret) are **never** written to audit logs
- No FK constraints on `entity_id` — entities can be deleted but their audit logs must persist

---

## Functional Requirements

### FR1 — Immutability

- Audit logs are **NEVER** updated or deleted via application code
- No delete endpoint for audit logs
- Retention cleanup happens **only via scheduled command**, not user action
- Database: do not grant DELETE privilege on `audit_logs` to the application DB user

### FR2 — Async writing

- Audit log writes must add **< 5ms** to the HTTP request
- Use queued jobs — fire-and-forget
- Context (user_id, ip_address, tenant_id) captured **at dispatch time**, not at job execution

### FR3 — Multi-tenant isolation

- All audit log queries include `WHERE tenant_id = ?`
- Owner/Admin of Tenant A cannot see Tenant B's logs
- Auth events (`tenant_id = null`) are excluded from the tenant viewer by default

### FR4 — Audit viewer access

- Only Owner and Admin can access the audit log viewer
- Controlled by `audit:view` permission — **must be added to the RBAC seeder**

### FR5 — Viewer UI requirements

- Timeline view — newest first
- Filter by: user, action type, date range, entity type
- Pagination: 20 items per page
- Human-readable labels: "John created task 'Fix bug #123'"
- Expandable rows: show `old_values` / `new_values` diff

**Not in v1:** Export CSV/PDF, real-time streaming, full-text search in values

---

## Non-Functional Requirements

| Requirement | Target |
|---|---|
| Write latency impact | < 5ms on HTTP request (async) |
| Viewer query time | < 100ms with 1M records (requires indexes) |
| Storage estimate | ~1KB/event → 1M events ≈ 1GB |
| Cross-tenant leak | Zero tolerance |
| Seeder noise | Seeder/migration events must NOT generate audit logs |

---

## Constraints

- Do not use `owen-it/laravel-auditing` package — build custom for full control
- Must fit Clean Architecture (Domain → Application → Infrastructure)
- `tenant_id` always explicit in all audit queries
- Queue driver must be configured (database queue acceptable for v1)
- `old_values` for Updates must be captured **before** the Use Case executes the update

---

## Success Criteria (Testable)

- [ ] Task create → `task.created` logged with correct `new_values`
- [ ] Task update → `task.updated` logged with both `old_values` and `new_values`
- [ ] Task delete → `task.deleted` logged with snapshot in `old_values`
- [ ] Project create/update/delete → logged correctly
- [ ] Successful login → `auth.login` logged
- [ ] Failed login → `auth.login_failed` logged with email in metadata
- [ ] Logout → `auth.logout` logged
- [ ] Audit viewer accessible by Owner → 200
- [ ] Audit viewer accessible by Admin → 200
- [ ] Audit viewer blocked for Member → 403
- [ ] Audit viewer blocked for Guest → 403
- [ ] User from Tenant A cannot see Tenant B's logs
- [ ] Deleting a Task does not delete its audit log records
