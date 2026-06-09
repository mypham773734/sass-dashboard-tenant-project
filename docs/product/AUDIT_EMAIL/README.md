# Audit Email System — Future Enhancement

**Status:** Planning — Blocked by MAIL_SERVICE  
**Last Updated:** 2026-06-09
**Estimated Effort:** 18-26 hours
**Scope:** Database-backed, per-tenant email configuration UI (Phase 2)

---

## Overview

Per-tenant daily email digests of audit logs with database-backed configuration.

**NOT in MVP scope.** This is a Phase 2 enhancement to MAIL_SERVICE.

**Current Phase:** Implement MAIL_SERVICE (`docs/product/MAIL_SERVICE/`) first — config-only, no UI.

Once MAIL_SERVICE is stable, add AUDIT_EMAIL — per-tenant config stored in database.

---

## Features (When Implemented)

Enables tenants to receive daily email digests of audit logs with:
- Configurable recipients and event filters (stored in `audit_email_configs` table)
- Automatic daily scheduling per tenant
- Manual send on-demand
- Report history and replay capability (stored in `audit_email_reports` table)
- Full integration with existing Audit System

---

## Quick Links

| Document | Content |
|----------|---------|
| [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) | Clarification questions, assumptions, functional specs |
| [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) | System design, data model, use cases, flows |
| [03-IMPLEMENTATION_PLAN.md](./03-IMPLEMENTATION_PLAN.md) | Detailed task breakdown, file checklist, phases |

---

## Key Assumptions

1. Per-tenant email recipients (stored in audit_email_configs table)
2. Schedule time in UTC (converted to tenant timezone for display)
3. Async queue-based delivery (no blocking)
4. Report includes previous calendar day's logs
5. Can resend any report from last 30 days
6. Uses Laravel Mail facade (works with any mailer)

---

## Architecture Overview

`
Admin Panel
   |
   +-- AuditEmailConfigController (show/update config)
   |
   +-- AuditEmailReportController (list/view/send reports)
   |
   +-- Use Cases (business logic)
   |
   +-- Repositories (domain + eloquent)
   |
   +-- Queue Job (SendAuditEmailJob)
   |
   +-- Scheduled Command (daily trigger)
   |
   +-- Mailable + Email Template
   |
   +-- audit_email_configs table
   +-- audit_email_reports table
   +-- audit_logs table (existing)
`

---

## File Structure

`
docs/product/AUDIT_EMAIL/
+-- README.md (this file)
+-- 01-REQUIREMENTS.md
+-- 02-ARCHITECTURE.md
+-- 03-IMPLEMENTATION_PLAN.md

app/Domain/AuditEmail/
+-- Entities/
+-- Repositories/
+-- ValueObjects/

app/Application/AuditEmail/
+-- DTOs/
+-- UseCases/

app/Infrastructure/AuditEmail/
+-- Persistence/Repositories/
+-- Queue/Jobs/
+-- Mail/
+-- Commands/

app/Http/
+-- Controllers/ (AuditEmailConfigController, AuditEmailReportController)
+-- Requests/ (UpdateAuditEmailConfigRequest, SendAuditEmailReportRequest)

resources/
+-- views/admin/pages/audit/
�   +-- email-config.blade.php
�   +-- reports/ (index.blade.php, show.blade.php)
+-- views/emails/audit-report.blade.php
+-- views/components/ (reusable UI components)

tests/
+-- Unit/UseCases/
+-- Feature/Http/
+-- Feature/Security/
+-- Integration/
`

---

## Implementation Phases

| Phase | Duration | What |
|-------|----------|------|
| 1. Foundation | 4h | Migrations, models, entities, repositories |
| 2. Application | 6h | DTOs, use cases, business logic |
| 3. Infrastructure | 4h | Queue jobs, mailable, scheduled command |
| 4. HTTP | 3h | Controllers, requests, routes, authorization |
| 5. Frontend | 3h | Blade views, email template, components |
| 6. Testing | 6h | Unit, feature, security, integration tests |

---

## Next Steps

1. Review [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) � clarify Q1-Q6 with stakeholders
2. Validate [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) � confirm design decisions
3. Estimate [03-IMPLEMENTATION_PLAN.md](./03-IMPLEMENTATION_PLAN.md) � team capacity planning
4. Begin Phase 1 � create migrations and domain layer

---

## Integration Points

**Existing systems this feature uses:**
- Audit System (pp/Domain/Audit) � queries audit_logs
- AuditLogger (App\Application\Audit\AuditLoggerInterface) � logs config changes and sends
- Permission RBAC � only admins can configure
- Mail facade � sends via configured mailer

**No breaking changes** to existing code � this is additive.

