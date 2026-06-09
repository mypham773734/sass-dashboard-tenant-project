# Mail Service — Architecture

**Status:** Planning  
**Last Updated:** 2026-06-06

## System Overview & Layer Mapping

```
Presentation (Http/Controllers)
    ↓
Application Layer (No Laravel)
    ├── EmailHandlerInterface (contract)
    └── EmailDTO (data transfer)
    ↓
Infrastructure Layer (Laravel-specific)
    ├── MailService (orchestrator)
    ├── Handlers (AuditDigest, UserInvitation, TenantNotification)
    │   • Read: EloquentAuditRepository, Models
    │   • Return: EmailDTO
    ├── SendEmailJob (queue)
    │   • Calls handler
    │   • Renders Mailable
    │   • Sends via Mail facade
    │   • Logs to audit_logs
    ├── Mailable classes (render templates)
    └── SendScheduledEmailsCommand
        • Loads config
        • Loops enabled types + tenants
        • Dispatches jobs
    ↓
Laravel Mail facade
    ↓
SMTP / Sendmail / Other providers
```

**Clean Architecture Compliance:**
- Domain layer: None needed (pure config-driven)
- Application layer: Interfaces & DTOs only (no Laravel)
- Infrastructure layer: All implementations (handlers, jobs, command)
- Presentation layer: Controllers call `MailService::dispatch()`

## Core Classes

### EmailHandlerInterface (Application Layer)
```php
interface EmailHandlerInterface {
    public function handle(int $tenantId, array $context): EmailDTO;
    public function shouldRun(string $schedule): bool;
}
```

### EmailDTO (Application Layer)
```php
class EmailDTO {
    public function __construct(
        public readonly string $type,
        public readonly string $subject,
        public readonly array $recipients,
        public readonly string $template,
        public readonly array $data = [],
    ) {}
}
```

### MailService (Infrastructure Layer)
```php
class MailService {
    public function send(string $type, int $tenantId, array $context): void;
    public function dispatch(string $type, int $tenantId, array $context): void;
    public function dispatchScheduled(): void;
}
```

### SendEmailJob
- Receives: type, tenantId, context
- Calls handler
- Renders mailable
- Sends via Mail facade
- Logs to audit_logs

### SendScheduledEmailsCommand
- Runs daily via cron
- Loads config
- Checks schedules
- Dispatches jobs

## Data Flow

### On-Demand (e.g., user invitation)
1. Controller calls MailService::dispatch()
2. SendEmailJob queued
3. Worker picks up job
4. Handler generates EmailDTO
5. Mailable renders email
6. Mail::send() sends
7. Audit logged

### Scheduled (e.g., audit digest)
1. Cron triggers command
2. Command loads config, checks schedules
3. For each scheduled email:
   - Dispatch SendEmailJob for each tenant
4. Worker processes jobs (same as #2-7 above)

## No Database

✓ Email types in config
✓ Recipients in config  
✓ Templates in blade
✓ Handlers in PHP classes

No migrations, no tables!

## Integration

- Queries audit_logs (if needed by handler)
- Logs to audit_logs (all sends)
- Uses Mail facade (respects config/mail)
- Uses Queue system (respects config/queue)

