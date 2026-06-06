# Audit System — Architecture

---
Version: 1.1
Last Updated: 2026-06-06
Status: Approved
Author: Architecture Team
Approach: D — AuditLogger Service (Hybrid)
---

## System Overview

```mermaid
graph TB
    subgraph Presentation["🖥️ Presentation Layer"]
        Controller["TaskController\nProjectController\nTenantController"]
        AuditController["AuditController\n(read-only viewer)"]
        LoginController["LoginController\n(Auth — không sửa)"]
    end

    subgraph Application["📋 Application Layer"]
        UseCase["CreateTaskUseCase\nUpdateTaskUseCase\nDeleteTaskUseCase\n..."]
        AuditInterface["AuditLoggerInterface\n+ log(action, entityId, ...)"]
        GetAuditUseCase["GetAuditLogsUseCase"]
    end

    subgraph Domain["🔧 Domain Layer"]
        AuditEntity["AuditLog Entity"]
        AuditRepo["AuditRepositoryInterface"]
    end

    subgraph Infrastructure["⚙️ Infrastructure Layer"]
        QueuedLogger["QueuedAuditLogger\nimplements AuditLoggerInterface"]
        EloquentAudit["EloquentAuditRepository"]
        AuthListener["AuthAuditListener\n(Laravel built-in events)"]
        Job["WriteAuditLogJob\n[queue: audit]"]
        DB[("audit_logs\ntable")]
    end

    Controller -->|invoke| UseCase
    UseCase -->|"audit->log()"| AuditInterface
    AuditInterface -->|implemented by| QueuedLogger
    QueuedLogger -->|"dispatch job"| Job
    Job -->|via| EloquentAudit
    EloquentAudit -->|implements| AuditRepo
    EloquentAudit -->|write| DB

    LoginController -->|"Illuminate\Auth\Events\Login"| AuthListener
    AuthListener -->|"dispatch job"| Job

    AuditController -->|invoke| GetAuditUseCase
    GetAuditUseCase -->|uses| AuditRepo
    AuditRepo -->|uses| AuditEntity
```

**Caption:** AuditLogger được inject vào Use Cases. Auth events dùng Laravel built-in — không cần sửa LoginController. Cả hai đều ghi qua cùng 1 Queue Job.

---

## So Sánh Với Approach C (Domain Events)

```mermaid
graph LR
    subgraph "Approach C — Domain Events (❌ nhiều boilerplate)"
        UC1["CreateTaskUseCase"] -->|dispatch| E1["TaskCreatedEvent"]
        E1 -->|listener| H1["handleTaskCreated()"]
        H1 --> J1["WriteAuditLogJob"]

        UC2["UpdateTaskUseCase"] -->|dispatch| E2["TaskUpdatedEvent"]
        E2 -->|listener| H2["handleTaskUpdated()"]
        H2 --> J2["WriteAuditLogJob"]

        note1["10 entities × 4 CRUD\n= 40 Event classes\n+ 40 handlers\n+ 40 registrations"]
    end

    subgraph "Approach D — AuditLogger Service (✅ scale tốt)"
        UC3["CreateTaskUseCase"] -->|"audit->log()"| AL["AuditLoggerInterface"]
        UC4["UpdateTaskUseCase"] -->|"audit->log()"| AL
        AL -->|"1 implementation"| J3["WriteAuditLogJob"]

        note2["10 entities × 4 CRUD\n= 1 dòng mỗi Use Case\n0 Event classes cần tạo"]
    end
```

---

## Data Model

```mermaid
erDiagram
    AUDIT_LOGS {
        bigint id PK
        int tenant_id "nullable — null cho auth events"
        int user_id "nullable — null cho system actions"
        string action "task.created, auth.login..."
        string entity_type "Task, Project, Tenant, User"
        int entity_id "nullable — soft reference"
        json old_values "nullable — null cho creates"
        json new_values "nullable — null cho deletes"
        string ip_address "nullable"
        text user_agent "nullable"
        json metadata "nullable — extra context"
        timestamp created_at "immutable — không có updated_at"
    }

    USERS ||--o{ AUDIT_LOGS : "performs"
    TENANTS ||--o{ AUDIT_LOGS : "scopes"
```

**Không dùng FK constraints:** Entity (Task, Project) có thể bị xoá nhưng audit log phải tồn tại mãi. FK gây lỗi khi xoá entity.

---

## AuditLogger — Class Design

```mermaid
classDiagram
    class AuditLoggerInterface {
        <<interface — Application Layer>>
        +log(action, entityId, entityType, newValues, oldValues, metadata) void
    }

    class QueuedAuditLogger {
        <<Infrastructure Layer>>
        +log(action, entityId, entityType, ...) void
        -captureContext() array
    }

    class NullAuditLogger {
        <<Infrastructure Layer — for tests>>
        +log(...) void
        +assertLogged(action) bool
    }

    class WriteAuditLogJob {
        <<Infrastructure — implements ShouldQueue>>
        -data: array
        +handle(AuditRepositoryInterface) void
        +queue: "audit"
    }

    AuditLoggerInterface <|.. QueuedAuditLogger
    AuditLoggerInterface <|.. NullAuditLogger
    QueuedAuditLogger --> WriteAuditLogJob : dispatch
```

**`NullAuditLogger`** dùng trong tests — không dispatch queue, chỉ giữ log trong memory để assert.

---

## Event Flow — Create Task (Approach D)

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
    CreateTaskUseCase->>QueuedAuditLogger: log('task.created', task.id, 'Task', newValues)
    Note over QueuedAuditLogger: capture session/IP/userId ngay tại đây
    QueuedAuditLogger->>WriteAuditLogJob: dispatch(data array)
    CreateTaskUseCase-->>TaskController: TaskEntity
    TaskController-->>Browser: redirect 302

    Note over WriteAuditLogJob,DB: Async — queue worker xử lý

    WriteAuditLogJob->>EloquentAuditRepo: create(AuditLog)
    EloquentAuditRepo->>DB: INSERT INTO audit_logs
```

**Caption:** Context (session, IP, user) được capture tại `QueuedAuditLogger::log()` — trước khi dispatch job. Job chỉ nhận plain array data, không truy cập session.

---

## Event Flow — Auth Login (Laravel Built-in)

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
        LaravelAuth--)AuthAuditListener: Illuminate\Auth\Events\Login (async)
        AuthAuditListener->>WriteAuditLogJob: dispatch({action: auth.login, ...})
        WriteAuditLogJob->>DB: INSERT audit_logs
    else Login Failed
        LaravelAuth-->>LoginController: false
        LoginController-->>Browser: back with error
        LaravelAuth--)AuthAuditListener: Illuminate\Auth\Events\Failed (async)
        AuthAuditListener->>WriteAuditLogJob: dispatch({action: auth.login_failed, ...})
        WriteAuditLogJob->>DB: INSERT audit_logs
    end
```

**Caption:** Auth events dùng Laravel's built-in event system — không cần sửa LoginController. `AuthAuditListener` là listener duy nhất cho auth events.

---

## Clean Architecture Layer Mapping

```mermaid
graph LR
    subgraph Domain["Domain — Pure PHP"]
        AuditLogEntity["AuditLog Entity\n(id, tenantId, userId,\naction, entityType,\noldValues, newValues...)"]
        AuditRepoInterface["AuditRepositoryInterface\n+ create(AuditLog)\n+ paginateByTenant(tenantId, filters)"]
    end

    subgraph Application["Application — Orchestration"]
        AuditLoggerInterface["AuditLoggerInterface\n+ log(action, entityId, ...)"]
        GetAuditLogsUseCase["GetAuditLogsUseCase\n+ execute(tenantId, filters)"]
        UseCases["CreateTaskUseCase\nUpdateTaskUseCase\n... inject AuditLoggerInterface"]
    end

    subgraph Infrastructure["Infrastructure — Laravel"]
        QueuedAuditLogger["QueuedAuditLogger\nimplements AuditLoggerInterface\n→ dispatch WriteAuditLogJob"]
        EloquentAuditRepo["EloquentAuditRepository\nimplements AuditRepositoryInterface\ntoEntity() / toArray()"]
        AuthAuditListener["AuthAuditListener\nhandle(Login)\nhandle(Failed)\nhandle(Logout)"]
        WriteAuditLogJob["WriteAuditLogJob\nimplements ShouldQueue\nqueue = 'audit'"]
    end

    AuditLoggerInterface -->|implemented by| QueuedAuditLogger
    AuditRepoInterface -->|implemented by| EloquentAuditRepo
    UseCases -->|inject| AuditLoggerInterface
    QueuedAuditLogger -->|dispatch| WriteAuditLogJob
    WriteAuditLogJob -->|uses| AuditRepoInterface
    GetAuditLogsUseCase -->|uses| AuditRepoInterface
    AuthAuditListener -->|dispatch| WriteAuditLogJob
```

---

## Audit Log Viewer — UI Flow

```mermaid
flowchart TD
    A["Owner/Admin truy cập /audit"] --> B["AuditController@index"]
    B --> C{"authorize:\nOwner hoặc Admin?"}
    C -->|403| Z["⛔ Forbidden"]
    C -->|OK| D["GetAuditLogsUseCase\nexecute(tenantId, filters)"]
    D --> E["AuditRepository\npaginateByTenant()"]
    E --> F["SELECT * FROM audit_logs\nWHERE tenant_id = ?\nORDER BY created_at DESC\nLIMIT 20"]
    F --> G["Paginator of AuditLog entities"]
    G --> H["audit/index.blade.php\nTimeline view"]
    H --> I["Mỗi row:\n👤 User · 📋 Action label · 🕐 Relative time"]
    I --> J["Click → expand\nold_values vs new_values diff"]
```

---

## Queue Architecture

```mermaid
graph LR
    subgraph "HTTP Request (sync — < 5ms)"
        A["Use Case"] -->|"audit->log()"| B["QueuedAuditLogger"]
        B -->|"WriteAuditLogJob::dispatch(data)"| C["Queue"]
        B2["AuthAuditListener"] -->|"WriteAuditLogJob::dispatch(data)"| C
    end

    subgraph "Queue Worker (async)"
        C -->|"queue: audit"| D["WriteAuditLogJob::handle()"]
        D --> E["EloquentAuditRepository::create()"]
        E --> F[("audit_logs DB")]
    end

    style A fill:#e3f2fd
    style B fill:#e3f2fd
    style C fill:#fff9c4
    style D fill:#e8f5e9
    style E fill:#e8f5e9
    style F fill:#e8f5e9
```

```
QUEUE_CONNECTION=database
php artisan queue:work --queue=audit,default
```

Queue `audit` tách riêng để audit jobs không block business jobs.

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
    -- Không có updated_at — immutable
);

-- Indexes cho viewer queries
CREATE INDEX idx_tenant_created ON audit_logs (tenant_id, created_at DESC);
CREATE INDEX idx_tenant_user    ON audit_logs (tenant_id, user_id);
CREATE INDEX idx_tenant_action  ON audit_logs (tenant_id, action);
CREATE INDEX idx_entity         ON audit_logs (entity_type, entity_id);
```

---

## Retention Policy

```mermaid
graph LR
    A["Scheduled Command\nAuditCleanupCommand\n(daily)"] --> B{"created_at\n< now() - AUDIT_RETENTION_DAYS?"}
    B -->|Yes| C["DELETE FROM audit_logs\nWHERE created_at < threshold\nLIMIT 1000\n(batch để tránh lock table)"]
    B -->|No| D["Skip"]
    C --> E["Log: N records deleted"]
```

```env
AUDIT_RETENTION_DAYS=90   # default 90 ngày
AUDIT_ENABLED=true        # tắt khi local dev / tests
```

---

## File Structure

```
app/
├── Domain/
│   └── Audit/
│       ├── Entities/
│       │   └── AuditLog.php
│       └── Repositories/
│           └── AuditRepositoryInterface.php
│
├── Application/
│   └── Audit/
│       ├── AuditLoggerInterface.php          ← interface cho Use Cases inject
│       └── UseCases/
│           └── GetAuditLogsUseCase.php
│
├── Infrastructure/
│   ├── Audit/
│   │   ├── QueuedAuditLogger.php             ← implements AuditLoggerInterface
│   │   └── NullAuditLogger.php               ← dùng trong tests
│   ├── Persistence/Repositories/
│   │   └── EloquentAuditRepository.php
│   └── Queue/Jobs/
│       └── WriteAuditLogJob.php
│
├── Models/
│   └── AuditLog.php
│
└── Http/
    ├── Controllers/Admin/
    │   └── AuditController.php
    └── Listeners/
        └── AuthAuditListener.php             ← chỉ cho auth events

database/
└── migrations/
    └── 2026_06_06_000000_create_audit_logs_table.php

resources/views/admin/pages/audit/
└── index.blade.php
```

---

## Related Documents

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) — Functional requirements
- [03-APPROACHES.md](./03-APPROACHES.md) — Tại sao chọn Approach D
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Build steps
