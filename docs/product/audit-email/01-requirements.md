# Daily Audit Email Reports — Requirements

**Status:** Planning  
**Last Updated:** 2026-06-06

---

## Clarification Questions

| # | Question | Impacts |
|---|----------|---------|
| Q1 | Should each tenant have individual email recipients, or is it system-wide admins only? | DB schema (audit_email_configs table) / Authorization |
| Q2 | What time should the daily email be sent? (UTC time or tenant's timezone) | Scheduled command logic / Config structure |
| Q3 | Should admins be able to trigger manual "Send Now" for today's report? | Use Case design / Controller endpoints |
| Q4 | Which audit events should be filterable? (e.g., only task/project changes, or include auth events?) | Email template / Data aggregation logic |
| Q5 | Should the email include summary statistics (# of changes by type) or just a list? | Email template / Aggregation logic |
| Q6 | What's the max retention for "resend last report" — can we send a report from yesterday if needed? | Use Case logic / Data validation |

### Assumed Answers (for this plan)

- **A1:** Per-tenant email recipients (stored in audit_email_configs)
- **A2:** UTC time (configurable per tenant, displayed in their timezone)
- **A3:** Yes — "Send report now" button in admin panel
- **A4:** Filterable by action prefix (e.g., task.*, project.*, auth.*)
- **A5:** Summary stats (pie chart of counts by type) + scrollable list
- **A6:** Can send any report within last 30 days

---

## Assumptions

| # | Assumption | Reason |
|---|-----------|--------|
| A1 | Mail config comes from Laravel's config('mail') — not stored in DB | Simplifies implementation; mail server details are per-environment, not per-tenant |
| A2 | Each tenant has exactly one daily report schedule (one time per day) | Prevents report spam; aligns with "once a day" requirement |
| A3 | Report includes only audit logs from the previous calendar day (00:00–23:59 in tenant timezone) | Clear boundary; easier to reason about "daily" semantics |
| A4 | Admins configure email recipients in a multi-select UI (checkboxes of tenant members or free-text email list) | Flexible; doesn't require separate user roles |
| A5 | Email is sent via Laravel's Mail facade (not custom API) | Leverages existing mail infrastructure; supports all mailers (SMTP, Sendmail, etc.) |
| A6 | Scheduled command runs once daily in production (e.g., cron: * 0 * * * php artisan audit:send-daily-reports) | Standard Laravel approach; no external job scheduler needed |
| A7 | Report generation is async (queued job) — command only dispatches jobs, doesn't wait | Prevents CLI timeout; aligns with existing WriteAuditLogJob pattern |
| A8 | Failed email sends are retried by Laravel's queue (exponential backoff) | Standard practice; no custom retry logic needed |

---

## Functional Requirements

### FR1 — Configuration Management

Tenant admins can configure email settings via web form.

**Configuration fields:**
- Enabled: Boolean flag
- Recipients: Array of emails (validated)
- Event filters: Select action prefixes
- Schedule time: Time in UTC
- Timezone: Display timezone
- Include auth events: Boolean

### FR2 — Automatic Daily Sends

Daily scheduler sends reports at configured time to all enabled tenants.

### FR3 — Manual Report Sends

Admins can trigger sending a report for any past date within 30 days.

### FR4 — Report Content

Email includes header, summary stats, detailed log table, footer.

### FR5 — Report History

System caches reports for replay and audit trail in audit_email_reports table.

### FR6 — Audit Integration

All actions (config updates, sends) logged via audit_logs.

### FR7 — Multi-Tenant Isolation

Each tenant sees only their own config and reports.

### FR8 — Error Handling

Graceful degradation for mail failures, disabled configs, missing data.

