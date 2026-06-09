# Daily Audit Email Reports - Architecture

**Status:** Planning
**Last Updated:** 2026-06-06

## System Overview

This system consists of:
1. Configuration UI for admins to set recipients, filters, schedule
2. Async queue job to generate and send reports
3. Scheduled command to trigger daily sends
4. Report history cache in DB
5. Integration with existing Audit System

## Database Tables

**audit_email_configs**
- id (PK)
- tenant_id (FK, unique)
- enabled (boolean)
- recipients (JSON)
- action_filters (JSON)
- schedule_time (TIME)
- timezone (VARCHAR)
- include_auth (boolean)
- created_at, updated_at

**audit_email_reports**
- id (PK)
- tenant_id (FK)
- report_date (DATE, unique with tenant_id)
- logs_count (INT)
- sent_at (TIMESTAMP, nullable)
- sent_to (JSON)
- created_at, updated_at

## Use Cases

1. **GetAuditEmailConfigUseCase** - Read tenant config
2. **UpdateAuditEmailConfigUseCase** - Save config changes
3. **GenerateAuditEmailReportUseCase** - Build report from logs
4. **SendAuditEmailReportUseCase** - Dispatch email job

## Controllers

1. **AuditEmailConfigController** - show(), update()
2. **AuditEmailReportController** - index(), show(), sendNow()

## Queue Jobs

**SendAuditEmailJob** - Async email delivery
- Generates report
- Sends via Mailable
- Logs audit event
- 3 retries

## Scheduled Command

**SendDailyAuditEmailsCommand**
- Runs daily at configurable time
- Queries all enabled configs
- Dispatches jobs for each tenant
- Returns immediately (jobs async)

## Integration

- Queries audit_logs table for report content
- Logs config changes and sends to audit_logs
- Uses Laravel Mail facade
- Uses existing AuditLoggerInterface for audit trail

