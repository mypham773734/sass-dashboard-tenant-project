# Permission & RBAC — Requirements

**Status:** Approved
**Last Updated:** 2026-06-06

---

## Roles (6)

| Role | Description | Key constraints |
|---|---|---|
| **Owner** | Created tenant, full control | Can delete tenant, cannot be demoted by others |
| **Admin** | Manage team + projects + tasks | Cannot delete tenant |
| **Manager** | Create/manage projects & tasks | Cannot manage team membership |
| **Member** | Own tasks only | Cannot see all tasks, cannot edit others' tasks |
| **Guest** | View-only | No create/edit/delete |
| **Custom** | Future placeholder | Not implemented in v1 |

**Role assignment rules:**
- Each user has exactly **one** role per tenant
- Roles are scoped per tenant: "admin" in tenant A ≠ "admin" in tenant B
- Default role when joining: **Member**
- Only Owner/Admin can change other users' roles
- User cannot demote themselves

---

## Permission Matrix (Canonical)

This is the single source of truth. Do not duplicate this matrix elsewhere.

### Tenant (5)

| Permission | Owner | Admin | Manager | Member | Guest |
|---|:---:|:---:|:---:|:---:|:---:|
| `tenant:view` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `tenant:edit` | ✅ | — | — | — | — |
| `tenant:delete` | ✅ | — | — | — | — |
| `tenant:invite_user` | ✅ | ✅ | — | — | — |
| `tenant:remove_user` | ✅ | ✅ | — | — | — |

### Project (5)

| Permission | Owner | Admin | Manager | Member | Guest |
|---|:---:|:---:|:---:|:---:|:---:|
| `project:view` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `project:view_all` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `project:create` | ✅ | ✅ | ✅ | — | — |
| `project:edit` | ✅ | ✅ | ✅ | — | — |
| `project:delete` | ✅ | ✅ | — | — | — |

### Task (12)

| Permission | Owner | Admin | Manager | Member | Guest |
|---|:---:|:---:|:---:|:---:|:---:|
| `task:view` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `task:view_own` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `task:view_all` | ✅ | ✅ | ✅ | — | — |
| `task:create` | ✅ | ✅ | ✅ | ✅ | — |
| `task:edit` | ✅ | ✅ | ✅ | — | — |
| `task:edit_own` | ✅ | ✅ | ✅ | ✅ | — |
| `task:edit_all` | ✅ | ✅ | ✅ | — | — |
| `task:edit_status` | ✅ | ✅ | ✅ | ✅ | — |
| `task:delete` | ✅ | ✅ | — | — | — |
| `task:delete_own` | ✅ | ✅ | ✅ | — | — |
| `task:delete_all` | ✅ | ✅ | — | — | — |
| `task:assign` | ✅ | ✅ | ✅ | — | — |

### Team (2)

| Permission | Owner | Admin | Manager | Member | Guest |
|---|:---:|:---:|:---:|:---:|:---:|
| `team:view` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `team:manage` | ✅ | ✅ | — | — | — |

### Dashboard (1)

| Permission | Owner | Admin | Manager | Member | Guest |
|---|:---:|:---:|:---:|:---:|:---:|
| `dashboard:view` | ✅ | ✅ | ✅ | ✅ | ✅ |

**Total: 25 permissions** (5 + 5 + 12 + 2 + 1)

> Note: `task:delete` (any task, regardless of ownership) differs from `task:delete_own` (only tasks the user created).

---

## Functional Requirements

### FR1 — Permission checking at 3 layers

```
Route:      ->middleware('can:task:create')
Controller: $this->authorize('create', [Task::class, $tenantId])
Policy:     TaskPolicy::create(User $user, int $tenantId): bool
```

### FR2 — Ownership-aware checks

For `task:edit_own`, `task:delete_own`, `task:view_own`:
- The Policy must check **both** the permission AND ownership (`created_by == user_id` or `assignee_id == user_id`)

### FR3 — Tenant isolation

- All permission checks must include explicit `$tenantId`
- Never derive tenant from session inside a Use Case or Policy
- Controller fetches `$tenantId = session('current_tenant_id')` and passes it down

### FR4 — Role assignment flow

- Invite: Owner/Admin selects role when sending invitation
- Default join: Member role assigned automatically
- Change: Only Owner/Admin can change roles

---

## Non-Functional Requirements

| Requirement | Target |
|---|---|
| Permission check latency | < 10ms (Redis cache hit) |
| Cache hit rate | > 90% |
| Cross-tenant data leak | Zero tolerance |
| Seeder idempotency | No duplicates on re-run |

---

## Success Criteria (Testable)

### Functional
- [ ] Owner can perform all 25 actions
- [ ] Member can create tasks, edit own tasks, view own tasks
- [ ] Guest cannot create, edit, or delete anything
- [ ] Member cannot edit another user's task
- [ ] User in Tenant A cannot access Tenant B data

### Performance
- [ ] Permission check < 10ms
- [ ] Cache invalidates correctly on role change

### Security
- [ ] GET /admin/task without `task:view` → 403
- [ ] Cross-tenant access attempt → 403
- [ ] Session-injected tenant_id manipulation → denied

---

## Constraints

- Must use `spatie/laravel-permission` v7.3+ (already installed)
- Must run on Laravel 13
- Cannot modify Spatie core code (only extend)
- Permissions are static (seeded, not user-created in v1)
- User belongs to exactly 1 role per tenant in v1

---

## Out of Scope (v1)

- Custom role creation by users
- Dynamic permission creation at runtime
- Permission inheritance between roles
- Multiple roles per user per tenant
- API token permission checks
