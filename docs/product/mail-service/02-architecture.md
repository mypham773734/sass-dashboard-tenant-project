# Mail Service — Architecture

**Status:** Draft  
**Last Updated:** 2026-06-09

---

## System Overview

```mermaid
graph TB
    subgraph Presentation["🖥️ Presentation Layer"]
        Controller["Controller\n(e.g. UserController)"]
    end

    subgraph Application["📋 Application Layer"]
        UseCase["Use Case\n(e.g. InviteUserUseCase)"]
        MailServiceInterface["MailServiceInterface\n+ dispatch(type, tenantId, context)\n+ send(type, tenantId, context)\n+ dispatchScheduled(now)"]
        EmailHandlerInterface["EmailHandlerInterface\n+ handle(tenantId, context): EmailDTO\n+ shouldSend(schedule, now): bool"]
        EmailDTO["EmailDTO\n(type, subject, recipients,\ntemplate, data)"]
    end

    subgraph Infrastructure["⚙️ Infrastructure Layer"]
        MailService["MailService\nimplements MailServiceInterface"]
        Handlers["Handlers\nUserInvitationHandler\nTenantNotificationHandler\nAuditDigestHandler"]
        SendMailJob["SendMailJob\nqueue: mail"]
        Mailables["Mailable classes"]
        DB[("audit_logs\n(mail.sent)")]
    end

    subgraph Queue["🔄 Queue Worker"]
        Worker["php artisan queue:work\n--queue=mail,default"]
    end

    subgraph Cron["⏰ Scheduler"]
        Command["SendScheduledEmailsCommand\nmail:send-scheduled"]
    end

    Controller -->|inject| UseCase
    UseCase -->|dispatch/send| MailServiceInterface
    MailServiceInterface -.->|bound to| MailService
    MailService -->|resolve handler| Handlers
    Handlers -.->|implements| EmailHandlerInterface
    Handlers -->|return| EmailDTO
    MailService -->|dispatch| SendMailJob
    SendMailJob -->|render| Mailables
    SendMailJob -->|Mail::send| Worker
    SendMailJob -->|log| DB
    Command -->|dispatchScheduled| MailService
```

---

## Clean Architecture Layer Mapping

```
Domain/
    (không có layer này — Mail Service là Infrastructure concern)

Application/Mail/
    Contracts/
        MailServiceInterface.php     ← Use Cases inject interface này
        EmailHandlerInterface.php    ← contract cho từng Handler
    DTOs/
        EmailDTO.php                 ← data transfer từ Handler → Job

Infrastructure/Mail/
    MailService.php                  ← implements MailServiceInterface
    NullMailService.php              ← dùng trong tests
    Handlers/
        UserInvitationHandler.php    ← implements EmailHandlerInterface
        TenantNotificationHandler.php
        AuditDigestHandler.php
    Jobs/
        SendMailJob.php              ← ShouldQueue, queue='mail'
    Mailables/
        UserInvitationMailable.php
        TenantNotificationMailable.php
        AuditDigestMailable.php
    Commands/
        SendScheduledEmailsCommand.php

config/
    mail-service.php

resources/views/emails/
    user-invitation.blade.php
    tenant-notification.blade.php
    audit-digest.blade.php
```

---

## Class Design

```mermaid
classDiagram
    class MailServiceInterface {
        <<interface — Application Layer>>
        +dispatch(type, tenantId, context) void
        +send(type, tenantId, context) void
        +dispatchScheduled(now) void
    }

    class MailService {
        <<Infrastructure — production>>
        -config: array
        +dispatch(type, tenantId, context) void
        +send(type, tenantId, context) void
        +dispatchScheduled(now) void
        -resolveHandler(type) EmailHandlerInterface
        -assertEnabled(type) void
    }

    class NullMailService {
        <<Infrastructure — tests only>>
        -sent: array
        +dispatch(type, tenantId, context) void
        +send(type, tenantId, context) void
        +dispatchScheduled(now) void
        +assertSent(type) bool
        +assertNotSent(type) bool
        +getSent() array
        +reset() void
    }

    class EmailHandlerInterface {
        <<interface — Application Layer>>
        +handle(tenantId, context) EmailDTO
        +shouldSend(schedule, now) bool
    }

    class AuditDigestHandler {
        <<Infrastructure>>
        -auditRepo: AuditRepositoryInterface
        +handle(tenantId, context) EmailDTO
        +shouldSend(schedule, now) bool
        -resolveRecipients(tenantId) array
    }

    class SendMailJob {
        <<Infrastructure — ShouldQueue>>
        -type: string
        -tenantId: int
        -context: array
        +queue = "mail"
        +handle(MailServiceInterface) void
    }

    MailServiceInterface <|.. MailService
    MailServiceInterface <|.. NullMailService
    EmailHandlerInterface <|.. AuditDigestHandler
    EmailHandlerInterface <|.. UserInvitationHandler
    EmailHandlerInterface <|.. TenantNotificationHandler
    MailService --> SendMailJob : dispatch
    MailService --> EmailHandlerInterface : resolves
```

**`NullMailService`** — dùng trong tests, không dispatch job thật, lưu in-memory cho assertions.  
Giống pattern `NullAuditLogger` của Audit System.

---

## Flow — On-Demand (User Invitation)

```mermaid
sequenceDiagram
    participant Browser
    participant UserController
    participant InviteUserUseCase
    participant MailService
    participant SendMailJob
    participant Handler
    participant DB

    Browser->>UserController: POST /user/invite
    UserController->>InviteUserUseCase: execute(dto, tenantId)
    InviteUserUseCase->>InviteUserUseCase: business logic (create invite)
    InviteUserUseCase->>MailService: dispatch('user_invitation', tenantId, context)
    Note over MailService: Validate type enabled, resolve handler class from config
    MailService->>SendMailJob: dispatch(type, tenantId, context)
    InviteUserUseCase-->>UserController: done
    UserController-->>Browser: redirect 302

    Note over SendMailJob,DB: Async — queue worker

    SendMailJob->>Handler: handle(tenantId, context)
    Handler-->>SendMailJob: EmailDTO (subject, recipients, data)
    SendMailJob->>SendMailJob: render Mailable → Mail::send()
    SendMailJob->>DB: INSERT audit_logs (action: mail.sent)
```

---

## Flow — Scheduled (Audit Digest)

```mermaid
sequenceDiagram
    participant Cron
    participant SendScheduledCmd
    participant MailService
    participant SendMailJob
    participant AuditDigestHandler
    participant DB

    Cron->>SendScheduledCmd: php artisan mail:send-scheduled
    SendScheduledCmd->>MailService: dispatchScheduled(now)

    loop mỗi email type có 'schedule' trong config
        MailService->>MailService: handler->shouldSend(schedule, now)?
        alt shouldSend = true
            loop mỗi tenant active
                MailService->>SendMailJob: dispatch('audit_digest', tenantId, {date})
            end
        end
    end

    Note over SendMailJob,DB: Async — queue worker (same path as on-demand)

    SendMailJob->>AuditDigestHandler: handle(tenantId, {date})
    AuditDigestHandler->>DB: SELECT audit_logs WHERE tenant_id=? AND date=?
    AuditDigestHandler->>AuditDigestHandler: resolveRecipients(tenantId) → query owners/admins
    AuditDigestHandler-->>SendMailJob: EmailDTO
    SendMailJob->>SendMailJob: Mail::send()
    SendMailJob->>DB: INSERT audit_logs (action: mail.sent)
```

---

## Multi-Tenant Recipients

Recipients **không** hardcode trong config. Handler tự resolve từ DB:

```
config/mail-service.php:
  audit_digest:
    handler: AuditDigestHandler   ← KHÔNG có 'recipients' key

AuditDigestHandler::handle($tenantId, $context):
  → query users WHERE tenant_id = $tenantId AND role IN ('owner', 'admin')
  → return EmailDTO với recipients = ['admin1@x.com', 'owner@x.com']
```

Mỗi tenant nhận email riêng với data riêng — đúng tenant isolation.

---

## AppServiceProvider Bindings

```php
// Giống AuditLoggerInterface pattern
$this->app->bind(
    \App\Application\Mail\Contracts\MailServiceInterface::class,
    \App\Infrastructure\Mail\MailService::class,
);
```

Trong tests:
```php
$this->app->bind(
    MailServiceInterface::class,
    NullMailService::class,
);
```

---

## Queue Architecture

```
HTTP Request (synchronous < 5ms)
    Use Case → MailServiceInterface::dispatch() → Queue::push(SendMailJob)

Queue Worker (asynchronous)
    SendMailJob::handle()
        → resolve handler → handle(tenantId, context) → EmailDTO
        → render Mailable
        → Mail::send()
        → INSERT audit_logs
```

```bash
# Worker ưu tiên queue 'mail' trước 'default'
php artisan queue:work --queue=mail,default
```

---

## Error Handling

| Scenario | Behavior |
|---|---|
| Email type không tồn tại trong config | Throw `InvalidArgumentException` — fail fast |
| Email type bị disabled | Return early, không throw — silent skip |
| Handler throw exception | Job fails → retry 3 lần → `failed_jobs` table |
| Mail facade fail (SMTP down) | Job fails → retry với backoff 60s |
| `MAIL_SERVICE_ENABLED=false` | Return early ở `MailService::dispatch()` |
