# Permission & RBAC — Requirements

---
Version: 1.0
Last Updated: 2026-06-04
Status: Approved
Author: Product Team
---

## Functional Requirements

### FR1: Role Management

**Roles:**
1. **Owner** — Created tenant, full control, can invite/remove users, delete tenant
2. **Admin** — Manage projects, tasks, team members (cannot delete tenant)
3. **Manager** — Create/manage projects & tasks, limited team visibility
4. **Member** — Create tasks, view assigned tasks, update own tasks
5. **Guest** — View-only access (dashboard, projects, own tasks)
6. **Custom** — (Placeholder for future user-defined roles)

**Rules:**
- Each user has exactly ONE role per tenant
- Roles are scoped to tenant (role "admin" in tenant A ≠ role "admin" in tenant B)
- Roles assigned when user joins tenant or by invite
- Owner can change other users' roles
- User cannot demote themselves

### FR2: Permission Matrix

**25 Permissions across 5 domains:**

#### Tenant (5)
- `tenant:view` — View tenant info
- `tenant:edit` — Update tenant name, settings
- `tenant:delete` — Delete entire tenant
- `tenant:invite_user` — Send invitations to users
- `tenant:remove_user` — Remove member from tenant

#### Project (5)
- `project:view` — View projects list
- `project:view_all` — View all projects (vs. own only)
- `project:create` — Create new project
- `project:edit` — Edit project details
- `project:delete` — Delete project

#### Task (10)
- `task:view` — View tasks (assigned to self)
- `task:view_own` — View own tasks only
- `task:view_all` — View all tasks in tenant
- `task:create` — Create new task
- `task:edit` — Edit task details
- `task:edit_own` — Edit only own tasks
- `task:edit_all` — Edit any task
- `task:edit_status` — Change task status
- `task:delete` — Delete task (own)
- `task:delete_all` — Delete any task
- `task:assign` — Assign task to other users

#### Team (2)
- `team:view` — View team members list
- `team:manage` — Manage team (invite, remove, change roles)

#### Dashboard (1)
- `dashboard:view` — Access admin dashboard

### FR3: Permission Checking

Permission checks happen at **3 levels:**

1. **Route Level** (middleware)
   ```
   Route::post('/task', [...])
       ->middleware('can:task:create');
   ```

2. **Controller Level** (explicit)
   ```php
   $this->authorize('create', [Task::class, $tenantId]);
   ```

3. **Policy Level** (resource-specific)
   ```php
   // TaskPolicy checks user role + resource ownership
   public function edit(User $user, Task $task): bool
   ```

### FR4: User Role Assignment

**When user joins tenant:**
- Auto-assign "Member" role (default)
- Owner can upgrade to Admin/Manager

**Via invitation:**
- Owner/Admin sends invite with role pre-selected
- New user gets that role on acceptance

**Via role change:**
- Only Owner/Admin can change user's role
- User cannot demote themselves

### FR5: Permission Caching & Performance

- Permission checks must be < 10ms per request
- Use Redis tags for cache invalidation
- Cache hits must be > 90%
- Invalidate cache only when role/permission changes

### FR6: Audit & Logging

- Log all permission denials (403 errors)
- Log all role/permission changes with timestamp + actor
- Enable permission audit reports (who has what access)

---

## Non-Functional Requirements

### Performance
- Permission check: < 10ms per DB hit
- Cache hit rate: > 90%
- Seeding 1000 users × 1000 permissions: < 5 min
- No N+1 queries in permission checks

### Security
- **No cross-tenant leaks:** User A cannot access tenant B's data
- **Explicit tenant scoping:** tenant_id visible in all queries
- **Immutable permissions:** Cannot change permission names (only enable/disable roles)
- **Audit trail:** All access attempts logged
- **Session validation:** Tenant context validated on every request

### Scalability
- Support 1000+ roles per tenant (unlikely, but possible)
- Support 10,000+ users per tenant
- Support 100,000+ tasks per tenant with instant permission check

### Maintainability
- Code must follow clean architecture (Domain → Application → Infrastructure)
- Use Laravel Policies (not custom authorization code)
- Permission names follow convention: `resource:action`
- Easy to add new permissions without code changes (seeder-based)

---

## Constraints & Assumptions

### Constraints
- Must use `spatie/laravel-permission` (already installed)
- Must run on Laravel 13
- Multi-tenant isolation MUST be explicit (no global scopes hiding tenant_id)
- Cannot modify Spatie core code (only extend)
- Must work with existing session-based tenant context

### Assumptions
- User belongs to exactly 1 role per tenant (not multiple roles)
- Permission inheritance is not needed (define all permissions per role)
- Permissions are static (seeded, not user-created)
- All resources (Task, Project) belong to exactly 1 tenant
- Current user stored in `Auth::user()`
- Current tenant stored in `session('current_tenant_id')`

---

## Dependencies

| Dependency | Version | Used For |
|---|---|---|
| spatie/laravel-permission | v7.3+ | Role/Permission management |
| Laravel | v13 | Authentication, policies |
| Redis | (available) | Cache + invalidation |
| MySQL | (available) | Persistence |

---

## Success Criteria (Testable)

### Functional Testing
- [ ] Owner role can perform all 25 actions
- [ ] Member role can create/edit own tasks, view own tasks
- [ ] Guest role can only view (no create/edit/delete)
- [ ] Member cannot edit other users' tasks
- [ ] User A (tenant 1) cannot access User B's (tenant 2) data

### Performance Testing
- [ ] Permission check < 10ms
- [ ] 1000 users seeded in < 5 min

### Security Testing
- [ ] Accessing /admin/task without `task:view` → 403
- [ ] Manually setting role to "admin" in browser → permission denied
- [ ] Cross-tenant access attempts → 403

### Integration Testing
- [ ] Role change reflected immediately in UI
- [ ] Permission cache invalidates on role change
- [ ] Seeder runs idempotently (no duplicates on re-run)

---

## Testing Strategy

### Unit Tests
- Policy methods (each permission check)
- User role/permission methods

### Integration Tests
- Full request flow: login → access route → check permission → render page
- Cross-tenant isolation
- Cache invalidation

### E2E Tests
- Login as different roles
- Verify UI elements visible/hidden
- Verify buttons work/disabled

**Target coverage:** > 90% of permission-related code

---

## Data Model Overview

```
User
├─ id, email, name
├─ many-to-many: roles (with tenant_id pivot)
└─ many-to-many: permissions (with tenant_id pivot)

Role
├─ id, name, guard_name, tenant_id
└─ many-to-many: permissions

Permission
├─ id, name, guard_name, tenant_id
└─ many-to-many: roles

Task / Project / Tenant
├─ ...(existing fields)...
└─ Many users have access (via roles)
```

---

## Related Documents

- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — How the system works
- [03-APPROACHES.md](./03-APPROACHES.md) — Why Approach B
- [04-IMPLEMENTATION_PLAN_B.md](./04-IMPLEMENTATION_PLAN_B.md) — Build steps

