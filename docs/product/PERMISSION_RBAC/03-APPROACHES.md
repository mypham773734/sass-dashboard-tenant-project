# Permission & RBAC — Approaches & Decision

---
Version: 1.0
Last Updated: 2026-06-04
Status: Approved (Approach B Selected)
Author: Architecture Team
---

## Overview

After analysis, **3 approaches** to implement RBAC with spatie/laravel-permission were evaluated. This document compares them and justifies the selected approach.

---

## Option A: Simple (Session-Based Scoping)

**Concept:** Use Spatie out-of-the-box, scope permissions by session `current_tenant_id` in middleware. Minimal schema changes.

### Implementation
```php
// Middleware: TenantScopedPermission
public function handle($request, $next) {
    $tenantId = session('current_tenant_id');
    Auth::user()->setCurrentTenant($tenantId);
    // All role/permission queries implicitly filtered
    return $next($request);
}

// Usage
Auth::user()->hasPermissionTo('task:create'); // auto-scoped
```

### Pros ✅
- Minimal schema changes (1 middleware only)
- Spatie used 100% standard
- Easy to understand & teach
- Fast implementation (1-2 days)
- No database migrations needed

### Cons ❌
- Relies on session state (risky if session not set)
- Hard to debug (hidden state, magic behavior)
- Cannot query permissions across tenants
- No explicit tenant_id in roles table → confusing
- Doesn't scale if user belongs to multiple tenants

### Suitable For
- Simple MVPs
- Single tenant per user only
- Very tight timeline (< 2 days)

### Risk Level 🔴
**Medium** — Session-based state is fragile

---

## Option B: Extended Spatie + Tenant-Aware Models (RECOMMENDED ✅)

**Concept:** Extend Spatie Role/Permission models with explicit `tenant_id`, override query methods. Clean, explicit, safe.

### Implementation
```php
// app/Models/Role.php (extended)
class Role extends SpatieLaravelPermissionRole {
    protected $fillable = ['name', 'guard_name', 'tenant_id'];
    
    public function scopeForTenant($query, $tenantId) {
        return $query->where('tenant_id', $tenantId);
    }
}

// app/Models/User.php (new methods)
public function hasPermissionInTenant($permission, $tenantId) {
    return $this->rolesForTenant($tenantId)
        ->whereHas('permissions', fn($q) => $q->where('name', $permission))
        ->exists();
}

// Usage
Auth::user()->hasPermissionInTenant('task:create', $tenantId); // explicit
```

### Pros ✅
- Explicit tenant_id everywhere (safe, traceable, debuggable)
- Clear separation of concerns
- Scales to multiple tenants per user (future)
- Works with clean architecture pattern
- Easy to audit & test
- explicit > implicit (prevents data leaks)

### Cons ⚠️
- Need to extend Spatie models (small effort)
- Must pass $tenantId to checks (more verbose)
- Migration required (small risk)
- Code more explicit (slightly more typing)

### Suitable For
- Production SaaS (this project ✅)
- Growth potential
- Multi-tenant scenarios
- Team collaboration

### Risk Level 🟡
**Low** — Explicit scoping prevents data leaks

---

## Option C: Full Multi-Tenancy Pattern (Advanced)

**Concept:** Global scopes on Role/Permission models. Spatie queries auto-scoped. No tenant_id passing needed.

### Implementation
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

// Usage
Auth::user()->hasPermissionTo('task:create'); // auto-scoped globally
```

### Pros ✅
- True multi-tenancy pattern (industry standard)
- No tenant_id passing (clean API)
- Automatic scoping everywhere
- Perfect for strict data isolation

### Cons ❌
- Global scopes hide behavior (harder to debug)
- Risk of forgetting scope → data leak
- Cannot query across tenants (no removeGlobalScope)
- Most complex to implement (3-4 days)
- Higher learning curve for team
- Harder to test (scope magic)

### Suitable For
- Strict regulatory environments
- Large teams
- Enterprise customers

### Risk Level 🔴
**High** — Magic scoping can hide bugs

---

## Comparison Matrix

| Aspect | A: Simple | **B: Recommended** | C: Advanced |
|---|---|---|---|
| **Complexity** | 🟢 Low | 🟡 Medium | 🔴 High |
| **Implementation** | 1-2 days | 2-3 days | 3-4 days |
| **Schema Changes** | Minimal | Small (tenant_id) | Medium (scopes) |
| **Debuggability** | ❌ Hard | ✅ Easy | ❌ Medium |
| **Data Safety** | ⚠️ Medium | ✅ High | ✅ High |
| **Multi-tenant User** | ❌ No | ✅ Yes (future) | ✅ Yes |
| **Spatie Compatibility** | 100% | 95% (override) | 90% (scopes) |
| **Production Ready** | ⚠️ Medium | ✅ High | ✅ High |
| **Team Learning Curve** | Low | Low | High |
| **Recommended For** | MVP | **This Project ✅** | Enterprise |

---

## Decision: **Approach B (Extended Spatie + Tenant-Aware Models)**

### Rationale

1. **Safety First:** Explicit `tenant_id` prevents accidental cross-tenant leaks
2. **Clarity:** Code is self-documenting (no hidden session state)
3. **Scalability:** Foundation for multi-tenant user support (future)
4. **Clean Architecture:** Aligns with layers (passing context down)
5. **Team Safety:** New developers won't accidentally forget tenant scope
6. **Debugging:** Stack traces show tenant context clearly
7. **Testing:** Easy to test with different tenant IDs

### Why Not A?
- Session state is fragile (what if middleware runs before tenant is set?)
- Cannot scale to user with multiple tenant access
- Difficult to unit test (depends on session)

### Why Not C?
- Overkill for current needs (MVP doesn't need this complexity)
- Global scopes hide behavior (harder to debug)
- Higher risk of scope leaks
- Team learning curve too steep now

---

## Implementation Plan Reference

Once this approach is approved, proceed to:
→ [04-IMPLEMENTATION_PLAN_B.md](./04-IMPLEMENTATION_PLAN_B.md)

---

## Approval Checklist

- [x] Approaches compared fairly
- [x] Pros/cons listed objectively
- [x] Risk levels assigned
- [x] Recommendation justified
- [x] Alternative paths documented

**Approved By:** Architecture Lead  
**Date:** 2026-06-04

