# Audit System — Overview

---
Version: 1.0
Last Updated: 2026-06-06
Status: Draft
Author: Architecture Team
---

## Problem Statement

Hệ thống hiện tại **không có cơ chế ghi nhận hành động người dùng**. Khi có sự cố xảy ra:

- Không biết **ai** đã xoá task/project
- Không biết **khi nào** dữ liệu bị thay đổi
- Không có bằng chứng khi tranh chấp giữa user
- Không đáp ứng được các yêu cầu compliance (GDPR, SOC2, ISO 27001)
- Không thể forensics khi bị tấn công nội bộ

---

## Solution Summary

Xây dựng **Audit Log System** ghi nhận toàn bộ hành động quan trọng trong hệ thống:

- **Who** — User nào thực hiện (+ IP, User-Agent)
- **What** — Hành động gì (task.created, project.deleted...)
- **When** — Timestamp chính xác
- **Where** — Trên resource nào (task_id=5, tenant_id=2)
- **Before/After** — State trước và sau khi thay đổi (for updates)

**Approach: Hybrid — AuditLogger Service + Laravel Auth Events**

- CRUD Use Cases → inject `AuditLoggerInterface` → gọi `$this->audit->log()` trực tiếp
- Auth events → Laravel built-in events (`Illuminate\Auth\Events\*`) → `AuthAuditListener`
- Ghi async qua Queue Job — không ảnh hưởng HTTP response time

**Key principle:** Audit log là **immutable** — chỉ ghi thêm, không bao giờ sửa hay xoá.

---

## Business Value

| Metric | Impact | Expected |
|---|---|---|
| **Security** | Phát hiện insider threats, tấn công nội bộ | Real-time alerts |
| **Compliance** | GDPR, SOC2 — chứng minh ai làm gì với data | Full audit trail |
| **Debugging** | Tái hiện sự cố production | < 5 phút forensics |
| **Accountability** | User biết mọi hành động đều được ghi | Ít lỗi do cẩu thả |
| **Business** | Upsell audit reports cho enterprise tier | Revenue opportunity |

---

## Scope

### ✅ In Scope (Phase 1-3)

- Ghi nhận CRUD operations: Task, Project, Tenant
- Ghi nhận Auth events: login, logout, login failed
- Ghi nhận Permission events: role changed, invite sent
- Multi-tenant scoping: mỗi audit log thuộc 1 tenant
- Audit log viewer: UI dạng timeline cho Owner/Admin
- Filters: by user, by action, by date range
- Data retention: configurable (default 90 ngày)

### ❌ Out of Scope (Future)

- Real-time alerts / webhook notifications
- Export to external SIEM (Splunk, Datadog)
- Automated anomaly detection
- Compliance report PDF generation
- Audit log cho API calls (future: API gateway)
- Granular field-level change tracking (only top-level for now)

---

## Timeline

| Phase | Duration | Deliverable |
|---|---|---|
| **Phase 1: Foundation** | 1 ngày | DB migration, Entity, Repository, AuditLogger service |
| **Phase 2: Domain Events** | 1 ngày | Events, Listeners, integration vào Use Cases |
| **Phase 3: Auth & Permission** | 0.5 ngày | Login/logout/role change events |
| **Phase 4: UI Viewer** | 1 ngày | Audit log page, filters, timeline view |
| **Phase 5: Testing** | 0.5 ngày | Feature tests, unit tests |
| **Total** | **4 ngày** | Production-ready Audit System |

---

## Success Criteria

- [ ] Mọi Create/Update/Delete trên Task, Project, Tenant đều có audit log
- [ ] Mọi login/logout/failed login đều được ghi
- [ ] Mọi role assignment/change đều được ghi
- [ ] Audit logs không bao giờ bị xoá hay sửa (immutable)
- [ ] UI hiển thị timeline đúng, filter theo user/action/date
- [ ] Cross-tenant isolation: Owner chỉ thấy logs của tenant mình
- [ ] Performance: ghi audit log không làm chậm request quá 5ms
- [ ] Tests cover toàn bộ action types

---

## Key Stakeholders

| Role | Responsibility |
|---|---|
| **Product Lead** | Define which actions cần audit, retention policy |
| **Architect** | Design data model, event system, clean arch integration |
| **Tech Lead** | Implementation review, performance sign-off |
| **Engineer** | Implementation |
| **QA** | Verify all audit events fire correctly |

---

## Related Documents

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) — Functional & non-functional requirements
- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — Data model, event flow, diagrams
- [03-APPROACHES.md](./03-APPROACHES.md) — So sánh 3 approaches, lý do chọn
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Step-by-step build plan

---

## Next Steps

1. → Review requirements
2. → Review architecture diagrams
3. → Approve approach (Recommended: Domain Events)
4. → Start Phase 1 implementation
