# Permission & RBAC — Architecture

---
Version: 1.0
Last Updated: 2026-06-04
Status: Approved
Author: Architecture Team
---

## System Overview

```mermaid
graph TB
    A["🌐 Browser"] -->|HTTP Request| B["🛣️ Route"]
    B -->|Middleware| C["🔐 Permission Check"]
    C -->|Validate| D["👤 User.hasPermissionInTenant()"]
    D -->|Query| E["💾 Redis Cache / DB"]
    E -->|Hit/Miss| F{"Permission?"}
    F -->|✅ Yes| G["✔️ Allow"]
    F -->|❌ No| H["⛔ Deny 403"]
    G -->|Policy| I["📋 TaskPolicy.edit()"]
    I -->|Ownership?| J{"Owner or\nAdmin?"}
    J -->|✅| K["✔️ Execute Action"]
    J -->|❌| H
    K -->|Response| A
```

**Caption:** Request flow with permission checking at 3 layers (Middleware → Controller → Policy)

---

## Data Model

```mermaid
erDiagram
    USER ||--o{ ROLE : "has (many-to-many)"
    USER ||--o{ PERMISSION : "has (many-to-many)"
    ROLE ||--o{ PERMISSION : "has (many-to-many)"
    TENANT ||--o{ ROLE : "scopes"
    TENANT ||--o{ PERMISSION : "scopes"
    TASK ||--o{ USER : "created_by"
    TASK ||--o{ USER : "assigned_id"
    
    USER {
        int id
        string email
        string name
    }
    ROLE {
        int id
        string name
        int tenant_id
        string guard_name
    }
    PERMISSION {
        int id
        string name
        int tenant_id
        string guard_name
    }
    TENANT {
        int id
        string name
        string slug
    }
    TASK {
        int id
        int tenant_id
        int project_id
        int created_by
        int assigned_to
        string title
    }
```

**Caption:** Role-based access control data model with tenant scoping

---

## Authorization Layers

```mermaid
flowchart TD
    A["📥 Incoming Request"] --> B["Layer 1: Route Middleware"]
    B -->|"can:task:create"| C{"User has\npermission?"}
    C -->|No| D["❌ 403 Forbidden"]
    C -->|Yes| E["Layer 2: Controller"]
    E -->|"authorize('create')"| F{"Policy allows?"}
    F -->|No| D
    F -->|Yes| G["Layer 3: Policy"]
    G -->|Business Logic| H{"Resource rules\nsatisfied?"}
    H -->|No| D
    H -->|Yes| I["✅ 200 OK"]
    D --> J["📤 Response"]
    I --> J
    
    style B fill:#e1f5ff
    style E fill:#f3e5f5
    style G fill:#e8f5e9
```

**Caption:** Three-layer authorization architecture (defense in depth)

---

## Permission Check Sequence

```mermaid
sequenceDiagram
    participant Browser
    participant Controller
    participant Policy
    participant User
    participant Redis
    participant DB
    
    Browser->>Controller: POST /task/1/edit
    Controller->>User: hasPermissionInTenant('task:edit', tenantId)
    User->>Redis: Get roles for (user, tenant)
    alt Cache Hit
        Redis-->>User: [Roles]
    else Cache Miss
        User->>DB: SELECT roles WHERE user_id=X AND tenant_id=Y
        DB-->>User: [Roles]
        User->>Redis: Cache for 10min
    end
    User->>User: Extract permissions from roles
    User-->>Controller: true/false
    
    Controller->>Policy: authorize('update', $task)
    Policy->>Policy: Check ownership (created_by == user_id)
    Policy-->>Controller: true/false
    
    alt Authorized
        Controller->>Controller: Execute usecase
        Controller-->>Browser: 200 OK
    else Denied
        Controller-->>Browser: 403 Forbidden
    end
```

**Caption:** Complete flow of permission checking with caching strategy

---

## Clean Architecture Integration

```mermaid
graph TB
    subgraph Presentation
        Route["🛣️ Route<br/>middleware:can:permission"]
        Controller["🎛️ Controller<br/>authorize policy"]
    end
    
    subgraph Application
        UseCase["📋 UseCase<br/>Business logic"]
    end
    
    subgraph Domain
        Policy["📜 Policy<br/>Business rules"]
        Entity["🔧 Entity<br/>Pure PHP"]
    end
    
    subgraph Infrastructure
        EloquentRepo["💾 Repository<br/>Spatie integration"]
        Redis["⚡ Redis<br/>Cache + Tags"]
    end
    
    Route -->|Layer 1: Route Guard| Controller
    Controller -->|Layer 2: Check Policy| Policy
    Controller -->|Layer 3: Invoke| UseCase
    UseCase -->|Use| Policy
    Policy -->|Query| EloquentRepo
    EloquentRepo -->|Cache| Redis
    EloquentRepo -->|Persist| Entity
    
    style Route fill:#fff3e0
    style Controller fill:#fff3e0
    style UseCase fill:#f3e5f5
    style Policy fill:#e8f5e9
    style Entity fill:#e8f5e9
    style EloquentRepo fill:#e0f2f1
    style Redis fill:#e0f2f1
```

**Caption:** Permission system integrated with clean architecture layers

---

## Role Hierarchy (Not Linear — Approach B)

```mermaid
graph LR
    subgraph S["Scope: Per Tenant"]
        O["👑 Owner<br/>All permissions"]
        A["🔑 Admin<br/>Manage team<br/>Create/delete projects"]
        M["📊 Manager<br/>Create/edit tasks<br/>Limited team access"]
        Me["👤 Member<br/>Own tasks only"]
        G["👁️ Guest<br/>View-only"]
    end
    
    O -->|more| A
    A -->|more| M
    M -->|more| Me
    Me -->|more| G
    
    C["⚙️ Custom<br/>(future)<br/>Admin-defined"]
    
    style O fill:#d4edda
    style A fill:#cfe2ff
    style M fill:#fff3cd
    style Me fill:#e2e3e5
    style G fill:#f8d7da
    style C fill:#e0e0e0
```

**Caption:** Role hierarchy — each role has permissions of lower roles (approximately)

---

## Permission Caching Strategy

```mermaid
graph TB
    A["👤 User A (tenant_id=5)"] -->|Request| B["Check Permission"]
    B -->|Query Redis| C{"Cache Hit?"}
    C -->|Yes: < 1ms| D["✅ Return cached roles"]
    C -->|No| E["❌ Query Database"]
    E -->|SELECT| F["roles WHERE user_id=5<br/>AND tenant_id=5"]
    F -->|Result| G["Cache in Redis<br/>TTL: 10min<br/>Tag: tenant:5:roles"]
    D --> H["Match permission<br/>to role"]
    G --> H
    H --> I{"Has<br/>permission?"}
    I -->|Yes| J["✅ Allow"]
    I -->|No| K["❌ Deny"]
    
    L["🔄 Invalidation"] -->|role changed| M["Delete cache<br/>Tag: tenant:5:roles"]
    M --> N["Next request<br/>Reloads from DB"]
    
    style C fill:#e8f5e9
    style D fill:#c8e6c9
    style E fill:#ffccbc
    style G fill:#ffe0b2
    style J fill:#a5d6a7
    style K fill:#ef9a9a
```

**Caption:** Caching with tag-based invalidation ensures performance + freshness

---

## File Structure

```
app/
├── Models/
│   ├── Role.php (extend Spatie, add scopeForTenant())
│   ├── Permission.php (extend Spatie, add scopeForTenant())
│   └── User.php (add rolesForTenant(), hasPermissionInTenant())
│
├── Policies/
│   ├── TaskPolicy.php (view, create, update, delete, assign)
│   ├── ProjectPolicy.php (view, create, update, delete)
│   └── TenantPolicy.php (view, edit, delete, inviteUser, removeUser)
│
├── Http/
│   ├── Controllers/Admin/TaskController.php
│   │   (updated with authorize() calls)
│   └── Middleware/
│       └── TenantScopedPermission.php (set tenant context)
│
└── Providers/
    └── AuthServiceProvider.php (register policies)

database/
├── migrations/
│   └── 2026_06_04_000000_add_tenant_id_to_permission_tables.php
│
└── seeders/
    └── RolePermissionSeeder.php (6 roles × 25 permissions)

resources/views/admin/pages/
├── task/
│   ├── index.blade.php (@can directives)
│   └── create.blade.php (@can on buttons)
```

---

## Technology Choices

| Component | Choice | Why |
|---|---|---|
| **Permission Library** | spatie/laravel-permission | Industry standard, active, works with Laravel Policies |
| **Tenant Scoping** | Explicit tenant_id in models | Safety > convenience, prevents leaks |
| **Caching** | Redis with tags | Fast < 1ms, tag-based invalidation safe for multi-tenant |
| **Authorization** | Laravel Policies | Matches Laravel conventions, integrates with authorize() |
| **API Auth** | Token-based (future) | Can extend Permission to API guards |

---

## Design Patterns Used

1. **Policy Pattern** — Each resource has a Policy that answers "can user X do action Y?"
2. **Tag-based Cache Invalidation** — Invalidate all cache for a tenant atomically
3. **Multi-layer Defense** — Route → Controller → Policy (defense in depth)
4. **Explicit over Implicit** — tenant_id always visible, no magic scoping

---

## Performance Assumptions

```mermaid
graph LR
    A["1000 users<br/>per tenant"] -->|avg case| B["Permission<br/>check<br/>< 10ms"]
    B -->|90% hits| C["Redis<br/>< 1ms"]
    B -->|10% misses| D["DB query<br/>+ cache<br/>< 20ms"]
    E["1M requests/day"] -->|impact| F["Redis<br/>10-20MB<br/>memory"]
```

**Caption:** Performance profile and resource usage estimates

---

## Security Model

```
Threat: User A accesses Task belonging to Tenant B
    ↓
Defense 1: Route middleware requires tenant_id in request
    ↓
Defense 2: Controller validates user has permission in tenant_id
    ↓
Defense 3: Policy checks resource ownership
    ↓
Defense 4: Query scoped by tenant_id
    ↓
Result: ✅ Prevented — data isolation maintained
```

---

## Related Documents

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) — What we're building
- [03-APPROACHES.md](./03-APPROACHES.md) — Why Approach B over A/C
- [04-IMPLEMENTATION_PLAN_B.md](./04-IMPLEMENTATION_PLAN_B.md) — How to build it

