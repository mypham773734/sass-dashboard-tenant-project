# Mail Service — Implementation Plan

**Status:** Planning  
**Last Updated:** 2026-06-09
**Estimated Duration:** 12-18 hours

---

## Key Advantage

NO database migrations needed! Everything in config file.

---

## Phases

### Phase 1: Refactor Docs (1-2h)
- Fix PHP code snippets (replace broken `\` placeholders)
- Update layer placement (Application vs Infrastructure)
- Add clean architecture diagram
- Mark AUDIT_EMAIL as "Future Enhancement"

### Phase 2: Core Infrastructure (2-3h)
- Create config/mail-service.php
- Create EmailHandlerInterface
- Create EmailDTO
- Create MailService
- Bind in ServiceProvider

### Phase 3: Handlers (2-3h)
- AuditDigestHandler
- UserInvitationHandler
- TenantNotificationHandler
- Register handlers

### Phase 4: Queue & Mailable (2h)
- SendEmailJob
- 3 Mailable classes
- 3 email templates

### Phase 5: Commands (1h)
- SendScheduledEmailsCommand
- Schedule parsing
- Register in console.php

### Phase 6: Integration (1h)
- Audit logging
- ServiceContainer setup
- Usage examples

**Total: 12-18 hours**

---

## Task List

| # | Task | Est. | Phase |
|---|------|------|-------|
| 1 | Refactor MAIL_SERVICE docs | 60m | 1 |
| 2 | Update AUDIT_EMAIL status | 15m | 1 |
| 3 | Create config file | 30m | 2 |
| 4 | Create interfaces & DTOs (Application) | 30m | 2 |
| 5 | Create MailService (Infrastructure) | 60m | 2 |
| 6 | ServiceProvider bindings | 15m | 2 |
| 7 | Create 3 handlers | 120m | 3 |
| 8 | Create SendEmailJob | 30m | 4 |
| 9 | Create 3 Mailable classes | 60m | 4 |
| 10 | Create 3 email templates | 60m | 4 |
| 11 | Create scheduled command | 60m | 5 |
| 12 | Schedule parsing logic | 30m | 5 |
| 13 | Register command in console.php | 15m | 5 |
| 14 | Audit logging integration | 30m | 6 |
| 15 | ServiceContainer setup | 15m | 6 |
| 16 | Documentation & examples | 15m | 6 |

---

## Files to Create

### Config
- config/mail-service.php

### Application Layer
- app/Application/Mail/Contracts/EmailHandlerInterface.php
- app/Application/Mail/DTOs/EmailDTO.php

### Infrastructure Layer
- app/Infrastructure/Mail/MailService.php
- app/Infrastructure/Mail/Handlers/AuditDigestHandler.php
- app/Infrastructure/Mail/Handlers/UserInvitationHandler.php
- app/Infrastructure/Mail/Handlers/TenantNotificationHandler.php
- app/Infrastructure/Mail/Jobs/SendEmailJob.php
- app/Infrastructure/Mail/Mailables/AuditDigestMailable.php
- app/Infrastructure/Mail/Mailables/UserInvitationMailable.php
- app/Infrastructure/Mail/Mailables/TenantNotificationMailable.php
- app/Infrastructure/Mail/Commands/SendScheduledEmailsCommand.php

### Templates
- resources/views/emails/audit-digest.blade.php
- resources/views/emails/user-invitation.blade.php
- resources/views/emails/tenant-notification.blade.php

### Tests (Optional)
- tests/Unit/Mail/MailServiceTest.php
- tests/Unit/Mail/Handlers/*Test.php
- tests/Feature/Mail/SendEmailJobTest.php
- tests/Feature/Mail/SendScheduledEmailsCommandTest.php

### Modifications
- app/Providers/AppServiceProvider.php (register MailService)
- routes/console.php (schedule command)

---

## Adding New Email Type

Example: Add "Weekly Report" email

### Step 1: Update config
```php
// config/mail-service.php
'weekly_report' => [
    'enabled' => true,
    'schedule' => 'weekly_friday_09_00',
    'template' => 'emails.weekly-report',
    'handler' => WeeklyReportHandler::class,
],
```

### Step 2: Create handler
```php
class WeeklyReportHandler implements EmailHandlerInterface {
    public function handle(int $tenantId, array $context): EmailDTO { }
    public function shouldRun(string $schedule): bool { }
}
```

### Step 3: Create mailable & template
```php
class WeeklyReportMailable extends Mailable { }
```

### Step 4: Register in provider
```php
$this->app->bind(
    'mail_handlers.weekly_report',
    new WeeklyReportHandler(...)
);
```

Done! No other changes needed.

---

## Config File Structure

```php
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
```

---

## Usage Examples

### On-Demand Send
```php
MailService::dispatch('user_invitation', $tenantId, [
    'email' => 'newuser@example.com',
    'sender_name' => auth()->user()->name,
]);
```

### Scheduled Send
```bash
# Add to crontab
0 0 * * * php /app/artisan mail:send-scheduled
```

### Direct Send
```php
MailService::send('tenant_notification', $tenantId, [
    'title' => 'Important Update',
]);
```

---

## Success Criteria

- ✓ Config-based (no DB tables)
- ✓ Pluggable handlers (EmailHandlerInterface)
- ✓ Scheduled + on-demand sends
- ✓ All tests pass (unit, feature, integration)
- ✓ Audit logged (write to audit_logs)
- ✓ Email templates render correctly
- ✓ Multi-tenant isolation enforced
- ✓ Clean architecture compliance (layers respected)
