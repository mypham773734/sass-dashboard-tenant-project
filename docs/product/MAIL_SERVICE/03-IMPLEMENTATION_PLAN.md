# Mail Service — Implementation Plan

**Status:** Planning  
**Last Updated:** 2026-06-06
**Estimated Duration:** 12-18 hours

---

## Key Advantage

NO database migrations needed! Everything in config file.

---

## Phases

### Phase 1: Core Infrastructure (3h)
- Create config/mail-service.php
- Create EmailHandlerInterface
- Create EmailDTO
- Create MailService
- Bind in ServiceProvider

### Phase 2: Handlers (4h)
- AuditDigestHandler
- UserInvitationHandler
- TenantNotificationHandler
- Register handlers

### Phase 3: Queue & Mailable (3h)
- SendEmailJob
- 3 Mailable classes
- 3 email templates

### Phase 4: Commands (2h)
- SendScheduledEmailsCommand
- Schedule parsing
- Register in console.php

### Phase 5: Integration (2h)
- Audit logging
- ServiceContainer setup
- Usage examples

### Phase 6: Testing (4h)
- Unit tests
- Job tests
- Integration tests

**Total: 12-18 hours**

---

## Task List

| # | Task | Est. | Phase |
|---|------|------|-------|
| 1 | Create config file | 30m | 1 |
| 2 | Create interfaces & DTOs | 30m | 1 |
| 3 | Create MailService | 60m | 1 |
| 4 | ServiceProvider bindings | 15m | 1 |
| 5 | Create 3 handlers | 120m | 2 |
| 6 | Create SendEmailJob | 30m | 3 |
| 7 | Create 3 Mailable classes | 60m | 3 |
| 8 | Create 3 email templates | 60m | 3 |
| 9 | Create scheduled command | 60m | 4 |
| 10 | Schedule parsing logic | 60m | 4 |
| 11 | Audit logging integration | 30m | 5 |
| 12 | ServiceContainer setup | 30m | 5 |
| 13 | Documentation & examples | 30m | 5 |
| 14 | Unit tests | 120m | 6 |
| 15 | Job/Queue tests | 60m | 6 |
| 16 | Integration tests | 60m | 6 |

---

## Files to Create

### Config
- config/mail-service.php

### Infrastructure
- app/Infrastructure/Mail/MailService.php
- app/Infrastructure/Mail/Handlers/EmailHandlerInterface.php
- app/Infrastructure/Mail/Handlers/AuditDigestHandler.php
- app/Infrastructure/Mail/Handlers/UserInvitationHandler.php
- app/Infrastructure/Mail/Handlers/TenantNotificationHandler.php
- app/Infrastructure/Mail/DTOs/EmailDTO.php
- app/Infrastructure/Mail/Jobs/SendEmailJob.php
- app/Infrastructure/Mail/Mail/AuditDigestMailable.php
- app/Infrastructure/Mail/Mail/UserInvitationMailable.php
- app/Infrastructure/Mail/Mail/TenantNotificationMailable.php
- app/Infrastructure/Mail/Commands/SendScheduledEmailsCommand.php

### Templates
- resources/views/emails/audit-digest.blade.php
- resources/views/emails/user-invitation.blade.php
- resources/views/emails/tenant-notification.blade.php

### Tests
- tests/Unit/Mail/MailServiceTest.php
- tests/Unit/Mail/AuditDigestHandlerTest.php
- tests/Unit/Mail/UserInvitationHandlerTest.php
- tests/Unit/Mail/TenantNotificationHandlerTest.php
- tests/Feature/Mail/SendEmailJobTest.php
- tests/Feature/Mail/SendScheduledEmailsCommandTest.php
- tests/Integration/Mail/MailServiceIntegrationTest.php

### Modifications
- app/Providers/AppServiceProvider.php (register handlers)
- routes/console.php (schedule command)

---

## Adding New Email Type

Example: Add "Weekly Report" email

### Step 1: Update config
\\\php
// config/mail-service.php
'weekly_report' => [
    'enabled' => true,
    'schedule' => 'weekly_friday_09_00',
    'template' => 'emails.weekly-report',
    'handler' => WeeklyReportHandler::class,
],
\\\

### Step 2: Create handler
\\\php
class WeeklyReportHandler implements EmailHandlerInterface {
    public function handle(string \, array \): EmailDTO { }
    public function shouldRun(string \): bool { }
}
\\\

### Step 3: Create mailable & template
\\\php
class WeeklyReportMailable extends Mailable { }
\\\

### Step 4: Register in provider
\\\php
\->register('weekly_report', new WeeklyReportHandler(...));
\\\

Done! No other changes needed.

---

## Config File

\\\php
// config/mail-service.php
return [
    'enabled' => env('MAIL_SERVICE_ENABLED', true),
    'queue' => env('MAIL_SERVICE_QUEUE', 'default'),
    
    'email_types' => [
        'audit_digest' => [
            'enabled' => env('AUDIT_DIGEST_ENABLED', true),
            'schedule' => env('AUDIT_DIGEST_SCHEDULE', 'daily_00_00'),
            'template' => 'emails.audit-digest',
            'handler' => AuditDigestHandler::class,
            'recipients' => ['admin@company.com'],
        ],
        
        'user_invitation' => [
            'enabled' => env('USER_INVITATION_ENABLED', true),
            'template' => 'emails.user-invitation',
            'handler' => UserInvitationHandler::class,
        ],
        
        'tenant_notification' => [
            'enabled' => env('TENANT_NOTIFICATION_ENABLED', true),
            'template' => 'emails.tenant-notification',
            'handler' => TenantNotificationHandler::class,
        ],
    ],
];
\\\

---

## Usage Examples

### On-Demand Send
\\\php
MailService::dispatch('user_invitation', \, [
    'email' => 'newuser@example.com',
    'sender_name' => auth()->user()->name,
]);
\\\

### Scheduled Send
\\\ash
# Add to crontab
0 0 * * * php /app/artisan mail:send-scheduled
\\\

### Direct Send
\\\php
MailService::send('tenant_notification', \, [
    'title' => 'Important Update',
]);
\\\

---

## Success Criteria

- ✓ Config-based (no DB tables)
- ✓ Pluggable handlers
- ✓ Scheduled + on-demand
- ✓ All tests pass
- ✓ Audit logged
- ✓ Email templates work in all clients
- ✓ Performance < 500ms per handler

