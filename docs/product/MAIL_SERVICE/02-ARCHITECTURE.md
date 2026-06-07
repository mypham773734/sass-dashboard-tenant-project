# Mail Service — Architecture

**Status:** Planning  
**Last Updated:** 2026-06-06

## System Overview

\\\
Application Code
    ↓
MailService Facade (send, dispatch, dispatchScheduled)
    ↓
  ┌─────────────────────────────────┐
  │ Pluggable Handlers              │
  │ - AuditDigestHandler            │
  │ - UserInvitationHandler         │
  │ - TenantNotificationHandler     │
  └─────────────────────────────────┘
    ↓
SendEmailJob (async queue)
    ↓
Mailable Classes (render templates)
    ↓
Laravel Mail facade
    ↓
SMTP / Sendmail / Other

Scheduled Command
    ↓
Check schedules in config
    ↓
Dispatch jobs for enabled types
\\\

## Core Classes

### EmailHandlerInterface
\\\php
interface EmailHandlerInterface {
    public function handle(string \, array \): EmailDTO;
    public function shouldRun(string \): bool;
}
\\\

### EmailDTO
\\\php
class EmailDTO {
    public string \;
    public string \;
    public array \;  // emails
    public string \;
    public array \;
}
\\\

### MailService
\\\php
class MailService {
    public function send(string \, string \, array \): void
    public function dispatch(string \, string \, array \): void
    public function dispatchScheduled(): void
}
\\\

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

