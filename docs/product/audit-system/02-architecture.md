# Audit System — Architecture

**Approach:** D — AuditLogger Service (Hybrid)
**Status:** Approved
**Last Updated:** 2026-06-06

---

## System Overview

```mermaid
graph TB
    subgraph Presentation
        Controller["TaskController / ProjectController / TenantController"]
        AuditController["AuditController (read-only viewer)"]
    end

    subgraph Application
        UseCase["CRUD Use Cases\ninject AuditLoggerInterface"]
        AuditLoggerInterface["AuditLoggerInterface\n+ log(action, entityId, ...)"]
        GetAuditLogsUseCase["GetAuditLogsUseCase"]
    end

    subgraph Domain
        AuditLogEntity["AuditLog Entity"]
        AuditRepoInterface["AuditRepositoryInterface"]
    end

    subgraph Infrastructure
        QueuedAuditLogger["QueuedAuditLogger\nimplements AuditLoggerInterface"]
        AuthAuditListener["AuthAuditListener\n(Laravel Auth Events)"]
        WriteAuditLogJob["WriteAuditLogJob\nqueue: audit"]
        EloquentAuditRepo["EloquentAuditRepository"]
        DB[("audit_logs")]
    end

    Controller -->|invoke| UseCase
    UseCase -->|audit->log()| AuditLoggerInterface
    AuditLoggerInterface -.->|implemented by| QueuedAuditLogger
    QueuedAuditLogger -->|dispatch| WriteAuditLogJob

    AuthAuditListener -->|dispatch| WriteAuditLogJob
    WriteAuditLogJob -->|via| EloquentAuditRepo
    EloquentAuditRepo -.->|implements| AuditRepoInterface
    EloquentAuditRepo -->|INSERT| DB

    AuditController -->|invoke| GetAuditLogsUseCase
    GetAuditLogsUseCase -->|uses| AuditRepoInterface
```

**Key design:** Both paths (CRUD Use Cases + Auth Events) write through the same `WriteAuditLogJob`. LoginController is never modified — `AuthAuditListener` hooks into Laravel's built-in event system.

---

## Data Model

```mermaid
erDiagram
    AUDIT_LOGS {
        bigint id PK
        int tenant_id "nullable — null for auth events"
        int user_id "nullable — null for system actions"
        varchar action "task.created, auth.login..."
        varchar entity_type "Task, Project, Tenant"
        bigint entity_id "nullable — soft reference, no FK"
        json old_values "nullable — null for creates"
        json new_values "nullable — null for deletes"
        varchar ip_address "nullable"
        text user_agent "nullable"
        json metadata "nullable — extra context"
        timestamp created_at "immutable — no updated_at"
    }

    USERS ||--o{ AUDIT_LOGS : "performs"
    TENANTS ||--o{ AUDIT_LOGS : "scopes"
```

**No FK on `entity_id`:** Tasks and Projects can be deleted; their audit logs must survive. Soft reference only.

---

## Class Design

```mermaid
classDiagram
    class AuditLoggerInterface {
        <<interface — Application Layer>>
        +log(action, entityId, entityType, newValues, oldValues, metadata) void
    }

    class QueuedAuditLogger {
        <<Infrastructure — production>>
        +log(action, entityId, entityType, ...) void
    }

    class NullAuditLogger {
        <<Infrastructure — tests only>>
        -logs: array
        +log(action, entityId, entityType, ...) void
        +assertLogged(action) bool
        +assertNotLogged(action) bool
        +getLogs() array
    }

    class WriteAuditLogJob {
        <<Infrastructure — ShouldQueue>>
        -data: array
        +queue = "audit"
        +handle(AuditRepositoryInterface) void
    }

    class AuthAuditListener {
        <<Infrastructure — Laravel events>>
        +handleLogin(Login) void
        +handleFailed(Failed) void
        +handleLogout(Logout) void
    }

    AuditLoggerInterface <|.. QueuedAuditLogger
    AuditLoggerInterface <|.. NullAuditLogger
    QueuedAuditLogger --> WriteAuditLogJob : dispatch
    AuthAuditListener --> WriteAuditLogJob : dispatch
```

**`NullAuditLogger`** is used in tests — no queue dispatch, stores logs in memory for assertions.

---

## Flow — Create Task (CRUD path)

```mermaid
sequenceDiagram
    participant Browser
    participant TaskController
    participant CreateTaskUseCase
    participant QueuedAuditLogger
    participant WriteAuditLogJob
    participant EloquentAuditRepo
    participant DB

    Browser->>TaskController: POST /task
    TaskController->>CreateTaskUseCase: execute(dto, tenantId, userId)
    CreateTaskUseCase->>CreateTaskUseCase: persist task via repository
    CreateTaskUseCase->>QueuedAuditLogger: log('task.created', task.id, ...)
    Note over QueuedAuditLogger: Capture session/IP/userId HERE — before dispatch
    QueuedAuditLogger->>WriteAuditLogJob: dispatch(data array)
    CreateTaskUseCase-->>TaskController: TaskEntity
    TaskController-->>Browser: redirect 302

    Note over WriteAuditLogJob,DB: Async — processed by queue worker

    WriteAuditLogJob->>EloquentAuditRepo: create(AuditLog)
    EloquentAuditRepo->>DB: INSERT INTO audit_logs
```

**Critical:** `session()`, `auth()->id()`, `request()->ip()` are captured inside `QueuedAuditLogger::log()` — at dispatch time. The Job receives only a plain data array and never accesses session/request.

---

## Flow — Auth Login (Laravel events path)

```mermaid
sequenceDiagram
    participant Browser
    participant LoginController
    participant LaravelAuth
    participant AuthAuditListener
    participant WriteAuditLogJob
    participant DB

    Browser->>LoginController: POST /login
    LoginController->>LaravelAuth: Auth::attempt()

    alt Login Success
        LaravelAuth-->>LoginController: true
        LoginController-->>Browser: redirect dashboard
        LaravelAuth--)AuthAuditListener: Illuminate\Auth\Events\Login
        AuthAuditListener->>WriteAuditLogJob: dispatch({action: auth.login})
        WriteAuditLogJob->>DB: INSERT
    else Login Failed
        LaravelAuth-->>LoginController: false
        LoginController-->>Browser: back with error
        LaravelAuth--)AuthAuditListener: Illuminate\Auth\Events\Failed
        AuthAuditListener->>WriteAuditLogJob: dispatch({action: auth.login_failed})
        WriteAuditLogJob->>DB: INSERT
    end
```

**LoginController is never modified.** `AuthAuditListener` is registered in `EventServiceProvider` and fires automatically.

---

## Clean Architecture Layer Mapping

```
Domain/Audit/
    Entities/AuditLog.php              — Pure PHP value object, all readonly
    Repositories/AuditRepositoryInterface.php  — create(), paginateByTenant()

Application/Audit/
    AuditLoggerInterface.php           — Interface injected into Use Cases
    UseCases/GetAuditLogsUseCase.php   — Query for viewer

Infrastructure/
    Audit/
        QueuedAuditLogger.php          — implements AuditLoggerInterface, dispatches job
        NullAuditLogger.php            — implements AuditLoggerInterface, for tests
    Listeners/
        AuthAuditListener.php          — handles Laravel Auth events
    Queue/Jobs/
        WriteAuditLogJob.php           — ShouldQueue, queue='audit'
    Persistence/Repositories/
        EloquentAuditRepository.php    — implements AuditRepositoryInterface

Models/
    AuditLog.php                       — Eloquent model, $timestamps=false

Http/Controllers/Admin/
    AuditController.php                — viewer, authorize Owner/Admin only
```

---

## Queue Architecture

```
HTTP Request (synchronous — target < 5ms)
    Use Case → audit->log() → QueuedAuditLogger → Queue::push(WriteAuditLogJob)
    AuthAuditListener                           → Queue::push(WriteAuditLogJob)

Queue Worker (asynchronous)
    WriteAuditLogJob::handle() → EloquentAuditRepository::create() → INSERT audit_logs
```

```bash
# Run queue worker with audit queue prioritized
php artisan queue:work --queue=audit,default
```

Queue `audit` is separate from `default` so audit jobs never block business-critical jobs.

---

## Database Schema & Indexes

```sql
CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NULL,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id   BIGINT UNSIGNED NULL,
    old_values  JSON NULL,
    new_values  JSON NULL,
    ip_address  VARCHAR(45) NULL,
    user_agent  TEXT NULL,
    metadata    JSON NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    -- No updated_at — immutable record
);

CREATE INDEX idx_tenant_created ON audit_logs (tenant_id, created_at DESC);  -- viewer default query
CREATE INDEX idx_tenant_user    ON audit_logs (tenant_id, user_id);           -- filter by user
CREATE INDEX idx_tenant_action  ON audit_logs (tenant_id, action);            -- filter by action
CREATE INDEX idx_entity         ON audit_logs (entity_type, entity_id);       -- entity lookup
```

---

## Retention Policy

```
Scheduled Command: AuditCleanupCommand (daily)
    DELETE FROM audit_logs
    WHERE created_at < NOW() - INTERVAL $retentionDays DAY
    LIMIT 1000 per batch    ← avoid table lock
```

```env
AUDIT_RETENTION_DAYS=90
AUDIT_ENABLED=true
```

The scheduled command never exposes a user-facing endpoint. Retention only via Artisan schedule.

---

## Audit Viewer UI Flow

```mermaid
flowchart TD
    A["Owner/Admin visits /audit"] --> B["AuditController@index"]
    B --> C{"authorize: audit:view\nOwner or Admin?"}
    C -->|403| Z["Forbidden"]
    C -->|OK| D["GetAuditLogsUseCase\nexecute(tenantId, filters)"]
    D --> E["AuditRepository::paginateByTenant()"]
    E --> F["SELECT WHERE tenant_id=? ORDER BY created_at DESC LIMIT 20"]
    F --> G["Paginator of AuditLog entities"]
    G --> H["audit/index.blade.php — timeline view"]
    H --> I["Each row: User · Action label · Relative time\nExpand → old_values vs new_values diff"]
```
