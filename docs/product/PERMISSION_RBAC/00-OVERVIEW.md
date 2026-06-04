# Permission & RBAC — Overview

---
Version: 1.0
Last Updated: 2026-06-04
Status: Approved
Author: Architecture Team
---

## Problem Statement

Currently, **all authenticated users have full access** to tenant data — no role-based access control exists. This creates security risks:

- User A (guest) can delete projects they shouldn't access
- No audit trail of who did what
- Cannot restrict data access by role
- Not suitable for team collaboration (need different permission levels)

---

## Solution Summary

Implement **Role-Based Access Control (RBAC)** using `spatie/laravel-permission`:

- **6 roles:** Owner, Admin, Manager, Member, Guest (+ custom)
- **25 permissions:** Granular control over Tenant, Project, Task, Team operations
- **Tenant-scoped:** Each tenant has independent roles/permissions
- **Multi-layer checking:** Route → Controller → Policy

**Key principle:** Explicit > Implicit (tenant_id always visible)

---

## Business Value

| Metric | Impact | Expected |
|---|---|---|
| **Data Security** | Prevent unauthorized access | 100% of operations scoped |
| **Team Collaboration** | Enable different permission levels | 5+ role types |
| **Compliance** | Audit trail, explicit access control | Audit logs available |
| **Scalability** | Support growing team sizes | 1000s of users per tenant |

---

## Scope

### ✅ In Scope

- Role creation (Owner, Admin, Manager, Member, Guest)
- Permission assignment to roles
- User role assignment per tenant
- Permission checks at route/controller/policy layers
- Permission-based UI rendering (@can/@role directives)
- Tests for role/permission matrix
- Seeding system with predefined roles

### ❌ Out of Scope

- Custom role creation by users (future)
- Dynamic permission creation (future)
- Permission inheritance (future)
- Multi-role per user per tenant (v1 = single role per user per tenant)
- API authentication with permissions (future)

---

## Timeline

| Phase | Duration | Deliverable |
|---|---|---|
| **Phase 1: Setup** | 1 day | DB migrations, models extended |
| **Phase 2: Policies** | 1 day | TaskPolicy, ProjectPolicy, seeder |
| **Phase 3: Integration** | 1 day | Routes protected, controllers updated |
| **Phase 4: Testing** | 1 day | UI updated, tests written |
| **Total** | **3-4 days** | Production-ready RBAC |

---

## Success Criteria

- [ ] All routes have permission checks
- [ ] Users cannot access data outside their tenant
- [ ] Permission matrix covers 100% of actions (Tenant, Project, Task, Team)
- [ ] Tests cover all 6 roles × main actions
- [ ] UI hides/shows buttons based on permissions
- [ ] Seeder runs cleanly on all tenants
- [ ] No cross-tenant permission leaks
- [ ] Zero 403 errors in logs for legitimate users

---

## Key Stakeholders

| Role | Responsibility |
|---|---|
| **Product Lead** | Approve requirements, success metrics |
| **Architect** | Design, approach selection, architecture review |
| **Tech Lead** | Supervise implementation, code review |
| **Engineer** | Execute implementation plan |
| **QA** | Test all role/permission combinations |

---

## Related Documents

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) — Detailed functional requirements
- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — System design & diagrams
- [03-APPROACHES.md](./03-APPROACHES.md) — Why we chose Approach B
- [04-IMPLEMENTATION_PLAN_B.md](./04-IMPLEMENTATION_PLAN_B.md) — Step-by-step plan

---

## Next Steps

1. ✅ Review this overview
2. → Review detailed requirements
3. → Review architecture & approaches
4. → Approve Approach B
5. → Start implementation Phase 1

