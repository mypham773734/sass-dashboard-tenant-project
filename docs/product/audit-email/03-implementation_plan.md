# Daily Audit Email Reports - Implementation Plan

**Status:** Planning
**Last Updated:** 2026-06-06
**Estimated Duration:** 18-26 hours

---

## Phase Breakdown

### Phase 1: Foundation (4 hours)
- Create migrations (audit_email_configs, audit_email_reports)
- Create Eloquent models
- Create domain entities and repository interfaces
- Create Eloquent repository implementations
- Bind repositories in AppServiceProvider

### Phase 2: Application Layer (6 hours)
- Create DTOs (4 types)
- Create 4 use cases with full business logic
- Integration with AuditLogger

### Phase 3: Infrastructure & Jobs (4 hours)
- Create async SendAuditEmailJob
- Create Mailable class and email template
- Create scheduled command

### Phase 4: HTTP Layer (3 hours)
- Create 2 controllers (AuditEmailConfigController, AuditEmailReportController)
- Create 2 form request validators
- Set up authorization (middleware/policies)

### Phase 5: Frontend (3 hours)
- Create settings page (enable/disable, recipients, filters, timezone)
- Create reports list page
- Create report detail page
- Create email template

### Phase 6: Testing (6+ hours)
- Unit tests for use cases
- Feature tests for controllers
- Security tests (tenant isolation)
- Integration tests (queue + mail)

---

## Task Breakdown

| # | Task | Est. | Phase |
|---|------|------|-------|
| 1 | Create migrations | 30m | 1 |
| 2 | Create Eloquent models | 20m | 1 |
| 3 | Create domain entities + VOs | 30m | 1 |
| 4 | Create repository interfaces | 20m | 1 |
| 5 | Create repository implementations | 45m | 1 |
| 6 | Bind repositories | 5m | 1 |
| 7 | Create DTOs | 20m | 2 |
| 8 | Create use cases | 90m | 2 |
| 9 | Create form requests | 20m | 4 |
| 10 | Create mailable class | 20m | 3 |
| 11 | Create queue job | 20m | 3 |
| 12 | Create scheduled command | 30m | 3 |
| 13 | Create controllers | 45m | 4 |
| 14 | Set up routes | 10m | 4 |
| 15 | Create blade views (config, reports) | 60m | 5 |
| 16 | Create email template | 20m | 5 |
| 17 | Create reusable components | 20m | 5 |
| 18 | Write unit tests | 90m | 6 |
| 19 | Write feature tests | 90m | 6 |
| 20 | Write security tests | 60m | 6 |

**Total: 18-26 hours** (including code review, debugging, refinements)

---

## Files to Create

### Domain Layer
- app/Domain/AuditEmail/Entities/AuditEmailConfig.php
- app/Domain/AuditEmail/Entities/AuditEmailReport.php
- app/Domain/AuditEmail/Entities/AuditEmailFilter.php
- app/Domain/AuditEmail/ValueObjects/EmailRecipientList.php
- app/Domain/AuditEmail/ValueObjects/ActionFilterList.php
- app/Domain/AuditEmail/Repositories/AuditEmailConfigRepositoryInterface.php
- app/Domain/AuditEmail/Repositories/AuditEmailReportRepositoryInterface.php

### Application Layer
- app/Application/AuditEmail/DTOs/GetAuditEmailConfigDTO.php
- app/Application/AuditEmail/DTOs/UpdateAuditEmailConfigDTO.php
- app/Application/AuditEmail/DTOs/SendAuditEmailReportDTO.php
- app/Application/AuditEmail/DTOs/AuditEmailConfigResponseDTO.php
- app/Application/AuditEmail/UseCases/GetAuditEmailConfigUseCase.php
- app/Application/AuditEmail/UseCases/UpdateAuditEmailConfigUseCase.php
- app/Application/AuditEmail/UseCases/GenerateAuditEmailReportUseCase.php
- app/Application/AuditEmail/UseCases/SendAuditEmailReportUseCase.php

### Infrastructure Layer
- database/migrations/YYYY_MM_DD_create_audit_email_configs_table.php
- database/migrations/YYYY_MM_DD_create_audit_email_reports_table.php
- app/Models/AuditEmailConfig.php
- app/Models/AuditEmailReport.php
- app/Infrastructure/AuditEmail/Persistence/Repositories/EloquentAuditEmailConfigRepository.php
- app/Infrastructure/AuditEmail/Persistence/Repositories/EloquentAuditEmailReportRepository.php
- app/Infrastructure/AuditEmail/Queue/Jobs/SendAuditEmailJob.php
- app/Infrastructure/AuditEmail/Mail/AuditEmailReport.php
- app/Infrastructure/AuditEmail/Commands/SendDailyAuditEmailsCommand.php

### HTTP Layer
- app/Http/Controllers/AuditEmailConfigController.php
- app/Http/Controllers/AuditEmailReportController.php
- app/Http/Requests/UpdateAuditEmailConfigRequest.php
- app/Http/Requests/SendAuditEmailReportRequest.php

### Frontend
- resources/views/admin/pages/audit/email-config.blade.php
- resources/views/admin/pages/audit/reports/index.blade.php
- resources/views/admin/pages/audit/reports/show.blade.php
- resources/views/emails/audit-report.blade.php
- resources/views/components/audit-email-filter-picker.blade.php
- resources/views/components/audit-report-stats.blade.php

### Tests
- tests/Unit/UseCases/GetAuditEmailConfigUseCaseTest.php
- tests/Unit/UseCases/UpdateAuditEmailConfigUseCaseTest.php
- tests/Unit/UseCases/GenerateAuditEmailReportUseCaseTest.php
- tests/Unit/UseCases/SendAuditEmailReportUseCaseTest.php
- tests/Feature/Http/AuditEmailConfigControllerTest.php
- tests/Feature/Http/AuditEmailReportControllerTest.php
- tests/Feature/Security/AuditEmailTenantIsolationTest.php
- tests/Integration/AuditEmailJobTest.php

### Modifications
- app/Providers/AppServiceProvider.php (add bindings)
- routes/web.php (add routes)
- routes/console.php (add scheduled command)

---

## Key Implementation Notes

### 1. Database Indexes
- audit_email_configs: (tenant_id) unique
- audit_email_reports: unique(tenant_id, report_date)

### 2. Timezone Handling
- Store all times in UTC
- Convert to tenant timezone for display
- Use date() in tenant timezone for day boundary

### 3. Tenant Isolation
- Always filter by session tenant_id
- Never accept tenant_id from request body
- Policy checks in all controller methods

### 4. Error Handling
- Validate all inputs in form requests
- Use DomainException for business rule violations
- Catch and log all exceptions in controller try-catch blocks

### 5. Audit Logging
- Log all config changes (old_values, new_values)
- Log all sends (report_date, recipient_count, logs_count)

### 6. Queue Configuration
- Use 'default' queue or 'audit' queue
- 3 retries per job
- Exponential backoff

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|-----------|
| Large audit_logs table | MEDIUM | Indexes already in place; limit query to date range |
| Queue not running | LOW | Document in README; use php artisan queue:work local |
| Email delivery failures | MEDIUM | Implement retry logic; audit log failed sends |
| Timezone confusion | MEDIUM | Store UTC; convert on display; test DST transitions |
| Cross-tenant data leak | HIGH | Strict tenant_id filtering in all queries + tests |

---

## Success Criteria

- [ ] All tests pass (unit, feature, security, integration)
- [ ] Tenant isolation verified (no cross-tenant leakage)
- [ ] Daily command successfully sends to all enabled tenants
- [ ] Admin can manually trigger sends for past dates
- [ ] Email templates render correctly in Gmail, Outlook, Apple Mail
- [ ] Performance: report generation < 500ms for 1000 logs
- [ ] Documentation complete (requirements, architecture, implementation)

