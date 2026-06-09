# Permission & RBAC — Entry Point

**Feature:** Role-Based Access Control (RBAC) using `spatie/laravel-permission`
**Status:** Approved — Approach B selected, ready to implement
**Last Updated:** 2026-06-06

---

## Problem

All authenticated users have full access to tenant data. No RBAC exists:

- Guest can delete projects they shouldn't touch
- No audit trail of who did what
- Cannot restrict data by role
- Not safe for team collaboration

## Solution

RBAC with explicit tenant-scoped roles and permissions:

- **6 roles:** Owner, Admin, Manager, Member, Guest (+ Custom placeholder)
- **26 permissions:** Covering Tenant, Project, Task, Team, Dashboard operations
- **Tenant-scoped:** Each tenant has independent roles and permissions
- **3-layer defense:** Route middleware → Controller authorize → Policy

**Core principle:** Explicit over implicit — `tenant_id` always passed explicitly, never hidden in session magic.

---

## Decision: Approach B (Extended Spatie + Tenant-Aware Models)

Three approaches were evaluated. **Approach B** was selected. Summary:

| | A: Session-scoped | **B: Extended Spatie ✅** | C: Global Scopes |
|---|---|---|---|
| Complexity | Low | Medium | High |
| Data safety | Medium (fragile session) | High (explicit) | High |
| Multi-tenant user support | No | Yes (future-ready) | Yes |
| Debuggability | Hard | Easy | Medium |
| Suitable for | MVP | **This project** | Enterprise |

**Why B, not A:** Session state is fragile. Cannot unit test without session. Cannot scale to user in multiple tenants.

**Why B, not C:** Global scopes hide behavior, harder to debug, higher risk of scope leaks, steep team learning curve.

---

## Reading Order

1. **[01-REQUIREMENTS.md](./01-REQUIREMENTS.md)** — Permission matrix (canonical), roles, testable criteria
2. **[02-ARCHITECTURE.md](./02-ARCHITECTURE.md)** — System design, diagrams, security model, file structure
3. **[03-IMPLEMENTATION.md](./03-IMPLEMENTATION.md)** — Step-by-step code, 4 phases, 3 days

---

## Quick Reference

### Roles (6)

| Role | Can do |
|---|---|
| **Owner** | Everything — created the tenant |
| **Admin** | Manage team + projects + tasks, cannot delete tenant |
| **Manager** | Create/manage projects & tasks, limited team visibility |
| **Member** | Own tasks only (create, edit own, view own) |
| **Guest** | View-only |
| **Custom** | Placeholder for future user-defined roles |

### Key APIs (Approach B)

```php
// Check permission in specific tenant (Controller, Policy)
Auth::user()->hasPermissionInTenant('task:create', $tenantId);

// Check role in specific tenant
Auth::user()->hasRoleInTenant('owner', $tenantId);

// Get user's roles in tenant
Auth::user()->rolesForTenant($tenantId);
```

### Permission naming convention

```
resource:action

Examples:
  task:create        task:edit_own       task:delete_all
  project:view       tenant:invite_user  team:manage
```

### 3-Layer Authorization

```
Layer 1 — Route middleware:    ->middleware('can:task:create')
Layer 2 — Controller:          $this->authorize('create', [Task::class, $tenantId])
Layer 3 — Policy:              TaskPolicy::create(User $user, int $tenantId): bool
```

---

## Prerequisites (already satisfied)

- [x] `spatie/laravel-permission` v7.3 installed
- [x] Laravel 13 running
- [x] Redis available
- [x] Clean architecture in place

---

## Timeline

| Phase | Work | Duration |
|---|---|---|
| 1 | DB migrations + extend models | Day 1 morning |
| 2 | Seeder + Policies | Day 1 afternoon |
| 3 | Routes + Controllers | Day 2 morning |
| 4 | UI (@can directives) + Tests | Day 2–3 |
| **Total** | | **3 days** |
