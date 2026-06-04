# Permission Architecture Design - RBAC with Spatie

**Status:** Draft for Review  
**Created:** 2026-06-04  
**Owner:** Architecture Team

---

## Executive Summary

This document outlines 3 approaches to implement RBAC (Role-Based Access Control) using `spatie/laravel-permission` in a multi-tenant SaaS environment. Each approach has different trade-offs between complexity, scalability, and flexibility.

---

## Current State

- **Package installed:** `spatie/laravel-permission` v7.3
- **Multi-tenancy:** Custom (TenantScope, session-based current_tenant_id)
- **Architecture:** Clean Architecture (Domain → Application → Infrastructure → Presentation)
- **Users:** No roles/permissions yet - all auth users have full access
- **Tenant structure:** Each tenant owns projects, tasks, team members

---

## Key Requirements

1. **Multi-tenant isolation:** Roles/permissions scoped to tenant
2. **Role flexibility:** 6 base roles (Owner, Admin, Manager, Member, Guest, Custom)
3. **Permission granularity:** 25+ permissions across Tenant, Project, Task, Team
4. **Integration:** Work with clean architecture (UseCase → Policy → Controller)
5. **Performance:** Minimal DB queries, cache-friendly

---

## 3 Approaches

### Approach A: Spatie Global + Manual Tenant Scoping (SIMPLE)

**Concept:** Use Spatie out-of-the-box, manually scope by tenant in middleware/checks.

**Architecture:**
```
Spatie Models (Role, Permission)
    ↓
Custom TenantScope Middleware (filters by current_tenant_id)
    ↓
User.hasPermissionTo('task:create') [auto-scoped to current tenant]
```

**Database:**
```sql
-- roles table (Spatie standard)
id, name, guard_name, created_at, updated_at

-- Add to role_has_permissions (pivot)
Add tenant_id column for tracking

-- Separate query: filter by session('current_tenant_id')
```

**Implementation:**
```php
// Middleware: TenantScopedPermission
public function handle($request, $next) {
    $tenantId = session('current_tenant_id');
    
    // Store tenant context
    Auth::user()->setCurrentTenant($tenantId);
    
    // All role/permission queries auto-filter
    return $next($request);
}

// Usage in Controller
if (Auth::user()->hasPermissionTo('task:create')) {
    // Implicitly scoped to current tenant
}
```

**Pros:**
- ✅ Minimal schema changes (1 middleware)
- ✅ Spatie usage stays standard
- ✅ Easy to understand/teach
- ✅ Fast to implement (1-2 days)

**Cons:**
- ❌ Relies on session state (risky if session not set)
- ❌ Hard to query permissions across tenants
- ❌ No explicit tenant_id in roles table → confusing later
- ❌ Doesn't scale if user belongs to multiple tenants simultaneously

**Best for:** Simple SaaS, single tenant per user, rapid MVP

---

### Approach B: Extended Spatie + Tenant-Aware Models (RECOMMENDED)

**Concept:** Extend Spatie Role/Permission models with explicit `tenant_id`, override query scopes.

**Architecture:**
```
Spatie Role/Permission Models (Extended with tenant_id)
    ↓
Role::where('tenant_id', $tenantId) [explicit scoping]
    ↓
User.rolesForTenant($tenantId) [new method]
    ↓
Policy/Gate check [tenant-aware]
```

**Database:**
```sql
-- roles table (extended)
id, name, guard_name, tenant_id, created_at, updated_at
Index: (tenant_id, name)

-- permissions table (extended)
id, name, guard_name, tenant_id, created_at, updated_at
Index: (tenant_id, name)

-- role_has_permissions (unchanged)
role_id, permission_id

-- model_has_roles (extended)
role_id, model_id, model_type, tenant_id
Index: (model_id, model_type, tenant_id)
```

**Implementation:**
```php
// app/Models/Role.php (override Spatie)
class Role extends SpatieLaravelPermissionRole {
    protected $fillable = ['name', 'guard_name', 'tenant_id'];
    
    public function scopeForTenant($query, $tenantId) {
        return $query->where('tenant_id', $tenantId);
    }
}

// app/Models/User.php
public function rolesForTenant($tenantId) {
    return $this->roles()->where('tenant_id', $tenantId);
}

public function hasPermissionInTenant($permission, $tenantId) {
    return $this->rolesForTenant($tenantId)
        ->whereHas('permissions', fn($q) => $q->where('name', $permission))
        ->exists();
}

// Middleware: Explicit tenant context (optional, for safety)
public function handle($request, $next) {
    $tenantId = session('current_tenant_id');
    $request->merge(['tenant_id' => $tenantId]);
    return $next($request);
}

// Usage in Controller
if (Auth::user()->hasPermissionInTenant('task:create', $tenantId)) {
    // Explicit tenant parameter
}

// Or in Policy
public function edit(User $user, Task $task) {
    return $user->hasPermissionInTenant('task:edit', $task->tenant_id);
}
```

**Pros:**
- ✅ Explicit tenant_id everywhere (safe, traceable)
- ✅ Can query user's roles across multiple tenants
- ✅ Works with user belonging to multiple tenants (future)
- ✅ Scales with multi-tenant growth
- ✅ Clear separation of concerns
- ✅ Easy to debug (audit trail)

**Cons:**
- ⚠️ Need to extend Spatie models (small complexity)
- ⚠️ Must pass $tenantId to permission checks (verbose)
- ⚠️ Migration to add tenant_id (small risk)
- ⏱️ Slightly longer implementation (2-3 days)

**Best for:** Production SaaS, growth potential, multi-tenant scenarios

---

### Approach C: Full Multi-Tenancy Pattern + Spatie (ADVANCED)

**Concept:** Separate role/permission tables per-tenant OR use Laravel's built-in multi-tenancy with Spatie.

**Architecture:**
```
Tenancy Middleware (set active tenant globally)
    ↓
Database connection switch per tenant (optional)
    ↓
Spatie queries auto-scoped (via scopes/global)
    ↓
No manual tenant_id passing needed
```

**Database (Option 1: Shared DB):**
```sql
-- Same as Approach B, but with global scope on Role/Permission
```

**Database (Option 2: Separate DB per tenant):**
```
Main DB:
  tenants table

Per-tenant DB:
  roles, permissions, role_has_permissions
```

**Implementation (Shared DB with Global Scope):**
```php
// app/Models/Role.php
class Role extends SpatieLaravelPermissionRole {
    protected static function boot() {
        parent::boot();
        static::addGlobalScope('tenant', function ($query) {
            $tenantId = auth()?->user()?->getCurrentTenant();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }
}

// Usage (no tenant_id parameter needed)
Auth::user()->hasPermissionTo('task:create'); // auto-scoped globally
```

**Pros:**
- ✅ True multi-tenancy pattern (industry standard)
- ✅ No tenant_id passing needed
- ✅ Query scoping happens automatically
- ✅ Works perfectly with multiple tenants per user

**Cons:**
- ❌ Requires global scope (harder to debug)
- ❌ Can't query across tenants without removing scope
- ❌ More complex setup (3-4 days)
- ❌ Higher learning curve for team
- ⚠️ Risk of forgetting scope = data leak

**Best for:** Enterprise, strict multi-tenancy, regulatory requirements

---

## Comparison Table

| Aspect | Approach A | Approach B | Approach C |
|---|---|---|---|
| **Complexity** | 🟢 Simple | 🟡 Medium | 🔴 High |
| **Setup time** | 1-2 days | 2-3 days | 3-4 days |
| **Schema changes** | Minimal | Small (tenant_id) | Medium (global scopes) |
| **Tenant isolation** | Session-based | Explicit | Automatic (risky) |
| **Multi-tenant user** | ❌ No | ✅ Yes | ✅ Yes |
| **Debugging** | Hard (hidden state) | Easy (explicit) | Medium (magic scopes) |
| **Scalability** | Low | High | High |
| **Production ready** | ⚠️ Maybe | ✅ Yes | ✅ Yes |
| **Spatie compatibility** | 100% | 95% (override) | 90% (scopes) |
| **Recommended for this project** | ❌ No | ✅ YES | ⚠️ Overkill |

---

## Recommendation: **Approach B (Extended Spatie + Tenant-Aware Models)**

### Why Approach B?

1. **Sweet spot:** Balances simplicity (Approach A) with safety & scalability (Approach C)
2. **Explicit > Implicit:** Tenant_id is visible and trackable
3. **Growth-proof:** If business need multi-tenant user later, easy to extend
4. **Clean Architecture fit:** Policies receive $tenantId param (explicit dependency)
5. **Team safety:** Hard to accidentally leak data across tenants
6. **Debugging:** Clear audit trail of which tenant check passed/failed

### What Approach B Looks Like in Practice

```
Controller Request
    ↓
Middleware: Extract current_tenant_id from session
    ↓
Controller: Pass $tenantId to UseCase
    ↓
UseCase: Call Policy with ($user, $task, $tenantId)
    ↓
Policy: Check user->hasPermissionInTenant('task:edit', $tenantId)
    ↓
Spatie: Query roles WHERE tenant_id = ? AND name = ?
    ↓
Response: Allow/Deny with explicit reasoning
```

---

## Implementation Roadmap (Approach B)

### Phase 1: Setup (Day 1)
- [ ] Create extended Role/Permission models
- [ ] Add tenant_id migration
- [ ] Create seeder for 6 base roles + 25 permissions per tenant
- [ ] Add `rolesForTenant()`, `hasPermissionInTenant()` methods to User

### Phase 2: Integration (Day 2)
- [ ] Create Policies: TaskPolicy, ProjectPolicy, TenantPolicy
- [ ] Add permission checks to Controllers (authorize calls)
- [ ] Add middleware: `can:task:create` on routes
- [ ] Update UseCase signature: accept $tenantId

### Phase 3: UI Layer (Day 2-3)
- [ ] Add @can/@role directives to Blade templates
- [ ] Update views to hide/show actions based on permissions
- [ ] Test role transitions (Member → Manager)

### Phase 4: Testing & Refinement (Day 3)
- [ ] Write tests for each role scenario
- [ ] Test permission caching
- [ ] Audit: Can user access data outside their tenant?

---

## Data Model Sketch (Approach B)

```
User
├─ id, email, name, password
├─ hasMany: roles (via role_user pivot with tenant_id)
└─ method: rolesForTenant($tenantId)

Role
├─ id, name, tenant_id
├─ belongsToMany: permissions
├─ scope: forTenant($tenantId)
└─ hasManyThrough: users (via pivot)

Permission
├─ id, name, tenant_id
├─ belongsToMany: roles
└─ example: task:create, task:edit, task:delete

Tenant
├─ id, name, slug
└─ hasMany: roles (filtered by tenant_id)
```

---

## Next Steps

### For Review (Please comment on):

1. **Do you agree Approach B is the best fit?** Or prefer A or C?
2. **Role count:** Stick with 6 roles (Owner, Admin, Manager, Member, Guest, Custom)?
3. **Permission granularity:** 25+ permissions enough, or need more/fewer?
4. **Tenant membership:** When user joins tenant → auto-assign "Member" role?
5. **Timeline:** Can we dedicate 3 days to implement Phase 1-2?

### Once Approved:
- [ ] Create detailed seeding strategy
- [ ] Write Policy methods (TaskPolicy.php, ProjectPolicy.php, etc)
- [ ] Define permission matrix (which role has which permissions)
- [ ] Create test scenarios
- [ ] Implement & verify

---

## References

- [Spatie Laravel Permission Docs](https://spatie.be/docs/laravel-permission/)
- [Laravel Policies](https://laravel.com/docs/authorization#creating-policies)
- [Multi-tenant SaaS patterns](https://www.stitcher.io/blog/laravel-multi-tenancy)

