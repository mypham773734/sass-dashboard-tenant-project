# Mail Service — Requirements

**Status:** Planning  
**Last Updated:** 2026-06-06

---

## Email Types (MVP)

### 1. Audit Digest (Scheduled daily)
- **What:** Daily email digest of audit logs
- **Schedule:** Configurable time (UTC) - default midnight
- **Recipients:** From config/mail-service.php
- **Handler:** AuditDigestHandler

### 2. User Invitation (On-demand)
- **What:** Email inviting user to join tenant
- **When:** Immediately when admin invites
- **Recipients:** Invited email address
- **Handler:** UserInvitationHandler

### 3. Tenant Notification (On-demand)
- **What:** Notify admins of important events
- **When:** Immediately or scheduled
- **Recipients:** Configured in config
- **Handler:** TenantNotificationHandler

---

## Key Design: Config-First

All configuration in config/mail-service.php:

\\\php
return [
    'enabled' => true,
    'queue' => 'default',
    
    'email_types' => [
        'audit_digest' => [
            'enabled' => true,
            'schedule' => 'daily_00_00',
            'template' => 'emails.audit-digest',
            'handler' => AuditDigestHandler::class,
            'recipients' => ['admin@company.com'],
        ],
        'user_invitation' => [
            'enabled' => true,
            'template' => 'emails.user-invitation',
            'handler' => UserInvitationHandler::class,
        ],
        'tenant_notification' => [
            'enabled' => true,
            'template' => 'emails.tenant-notification',
            'handler' => TenantNotificationHandler::class,
        ],
    ],
];
\\\

---

## Handler Interface

Each handler implements EmailHandlerInterface:

\\\php
interface EmailHandlerInterface {
    public function handle(string \, array \): EmailDTO;
    public function shouldRun(string \): bool;
}
\\\

Handler responsibilities:
- Build email content
- Query data (audit logs, users, etc.)
- Determine recipients
- Return EmailDTO

---

## MailService API

\\\php
// On-demand send
MailService::dispatch('user_invitation', \, [
    'email' => 'newuser@example.com',
]);

// Scheduled send
MailService::dispatchScheduled();  // run by command

// Direct send (immediate)
MailService::send('type', \, \);
\\\

---

## Functional Requirements

- **FR1:** Config-based email types (no DB tables)
- **FR2:** Pluggable handlers for each type
- **FR3:** Scheduled sends via command (cron)
- **FR4:** On-demand sends via MailService::dispatch()
- **FR5:** Async delivery via queue jobs
- **FR6:** Audit logging all sends
- **FR7:** Multi-tenant support
- **FR8:** Extensible (add new types = config + handler)

---

## No Database Tables

✓ Email types → config file
✓ Schedules → config file
✓ Recipients → config file
✓ Templates → blade files

Benefits:
- Simpler deployment (no migrations)
- Environment-based config (.env)
- Easy to version control

---

## Multi-Tenant Handling

- Each tenant can have different handlers/config
- Queue jobs include tenant_id
- Audit logs scoped to tenant_id
- Handlers respect tenant isolation

