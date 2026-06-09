# Mail Service — Config-Based Email System

**Status:** In Development  
**Last Updated:** 2026-06-09
**Estimated Effort:** 12-18 hours

## Overview

Unified mail sending system with config-based email types and pluggable handlers. No database tables needed.

**Email Types:**
- Audit digest (daily scheduled)
- User invitations (on-demand)
- Tenant notifications (on-demand)
- Extensible for more types

## Architecture at a Glance

```
Application Layer
  ├── EmailHandlerInterface (contract)
  └── EmailDTO (data transfer object)
       ↓
Infrastructure Layer
  ├── MailService (orchestrator)
  ├── Handlers (AuditDigest, UserInvitation, TenantNotification)
  ├── SendEmailJob (async queue)
  ├── Mailable classes (Blade templates)
  └── SendScheduledEmailsCommand (cron)
```

## Key Design Principles

- **No DB:** All config in `config/mail-service.php`
- **Pluggable:** Add new email types = config + handler + mailable
- **Multi-tenant:** Each handler receives `$tenantId`, respects tenant isolation
- **Async:** Uses Laravel queue for delivery
- **Auditable:** All sends logged to `audit_logs` table
- **Clean Architecture:** Handlers in Infrastructure, contracts in Application

## Quick Links

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) — Email types, config structure, requirements
- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — Service design, layer mapping, data flow  
- [03-IMPLEMENTATION_PLAN.md](./03-IMPLEMENTATION_PLAN.md) — Tasks, phases, file structure

## Adding a New Email Type

Three steps:

1. **Update config/mail-service.php:**
   ```php
   'my_email' => [
       'enabled' => true,
       'schedule' => 'daily_09_00',  // or remove for on-demand
       'template' => 'emails.my-email',
       'handler' => MyEmailHandler::class,
   ],
   ```

2. **Create handler** (implements `EmailHandlerInterface`):
   ```php
   class MyEmailHandler implements EmailHandlerInterface {
       public function handle(int $tenantId, array $context): EmailDTO { }
       public function shouldRun(string $schedule): bool { }
   }
   ```

3. **Create mailable + template** (Blade file)

Done! No other changes needed.

## Related Features

**AUDIT_EMAIL** (`docs/product/AUDIT_EMAIL/`) — Future enhancement for per-tenant email configuration in the database. Not in MVP scope.
