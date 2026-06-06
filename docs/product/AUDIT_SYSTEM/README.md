# Audit System — Documentation Index

**Status:** Draft | **Approach:** Domain Events + Queue | **Est:** 4 ngày

## Documents

| File | Mô tả | Status |
|---|---|---|
| [00-OVERVIEW.md](./00-OVERVIEW.md) | Problem statement, business value, scope, timeline | ✅ Done |
| [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) | Functional requirements, event taxonomy, data structure | ✅ Done |
| [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) | Data model, event flow diagrams, file structure | ✅ Done |
| [03-APPROACHES.md](./03-APPROACHES.md) | So sánh Package vs Observer vs Domain Events | ✅ Done |
| [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) | Step-by-step phases, code patterns, checklist | ✅ Done |

## Quick Summary

**What:** Ghi nhận mọi hành động quan trọng — ai làm gì, khi nào, trên resource nào.

**How:** Use Cases inject `AuditLoggerInterface` → `QueuedAuditLogger` → Queue Job → `audit_logs` table. Auth events dùng Laravel built-in listener.

**Why AuditLogger Service (not Domain Events):** Domain Events phù hợp khi 1 action có nhiều side effects ở nhiều domain. Audit là 1 side effect duy nhất — service injection đơn giản hơn, scale tốt hơn (thêm entity mới chỉ cần 1 dòng, không cần tạo Event class).

**Key Events:** Task/Project CRUD · Auth login/logout/failed · Role assignment · Tenant management

## Diagrams trong docs này

- [System overview](./02-ARCHITECTURE.md#system-overview) — Component graph
- [Create task audit flow](./02-ARCHITECTURE.md#event-flow--create-task) — Sequence diagram
- [Auth event flow](./02-ARCHITECTURE.md#event-flow--auth-login) — Login/logout sequence
- [Domain Events class hierarchy](./02-ARCHITECTURE.md#domain-events--class-hierarchy) — Class diagram
- [Audit viewer UI flow](./02-ARCHITECTURE.md#audit-log-viewer--ui-flow) — Flowchart
- [Queue architecture](./02-ARCHITECTURE.md#queue-architecture) — Async write design
- [Retention policy](./02-ARCHITECTURE.md#retention-policy) — Cleanup strategy
