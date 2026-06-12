# System Flow Analysis — Tổng quan

**Mục đích:** Phân tích flow của TOÀN BỘ hệ thống (request lifecycle, multi-tenancy, CRUD qua Clean Architecture, và các cross-cutting concerns: Audit / Mail / Notification), dựa trên code thực tế trong repo.

**Đối tượng:** Dev mới cần hiểu nhanh "request đi qua những đâu", hoặc dev cũ cần tra cứu khi debug.

---

## 📚 Cấu trúc tài liệu

| File | Nội dung |
|---|---|
| **[01-request-lifecycle.md](./01-request-lifecycle.md)** | Request đi từ `bootstrap/app.php` → Middleware (Auth, Tenant) → Controller. Cách `tenantContext()`/`authContext()` hoạt động, `TenantScope`. |
| **[02-feature-crud-flow.md](./02-feature-crud-flow.md)** | Flow CRUD chuẩn qua 4 layer Clean Architecture (Domain → Application → Infrastructure → Http), minh họa bằng feature Task. |
| **[03-cross-cutting-flows.md](./03-cross-cutting-flows.md)** | Các side-effect chạy "ngầm" sau khi UseCase thực thi: Audit Log, Mail Service, Notification System — và cách chúng liên kết với nhau. |

> Notification System đã có doc chi tiết riêng tại [`docs/product/notification-system/`](../product/notification-system/readme.md) (bao gồm cả [04-flow-analysis.md](../product/notification-system/04-flow-analysis.md)). Doc `03-cross-cutting-flows.md` ở đây chỉ tóm tắt và chỉ ra **mối liên hệ** giữa Audit/Mail/Notification trong cùng 1 request.

---

## 🗺️ Sơ đồ tổng quan (big picture)

```
┌─────────────────────────────────────────────────────────────────────┐
│  HTTP Request                                                         │
└───────────────┬───────────────────────────────────────────────────────┘
                ▼
        bootstrap/app.php
        ├─ routing: web / api / commands / channels
        ├─ middleware: redirectGuestsTo, alias 'chooseTenant'
        └─ withSchedule: mail:send-scheduled (everyMinute)
                ▼
        Middleware pipeline ('web' group)
        ├─ auth (session)
        ├─ SetDefaultTenant   → set session('current_tenant_id') nếu chưa có
        └─ chooseTenant (ChooseCurrentTenant) → 403 nếu không có tenant
                ▼
        Controller (Http layer)
        ├─ try { ... } catch (DomainException) { ... } catch (Exception) { ... }
        ├─ Request::validated() → DTO::fromArray()
        └─ UseCase::execute($dto, tenantContext()->getId(), ...)
                ▼
        Application layer — UseCase
        ├─ Validate business rules → throw DomainException
        ├─ Repository (Domain interface) → Eloquent (Infrastructure)
        └─ Side-effects (cross-cutting):
              ├─ AuditLoggerInterface->log()        → queue → audit_logs table
              ├─ NotificationServiceInterface->...  → queue → notifications table → broadcast (Echo)
              └─ MailServiceInterface->dispatch()   → queue → email
                ▼
        Response: redirect()/view() + flash message
```

---

## 🔑 Các khái niệm cốt lõi

| Khái niệm | File | Vai trò |
|---|---|---|
| `tenantContext()` | [`app/Shared/Tenant/TenantContext.php`](../../app/Shared/Tenant/TenantContext.php) | Đọc/ghi `session('current_tenant_id')` — KHÔNG dùng trong UseCase |
| `authContext()` | [`app/Shared/Auth/AuthContext.php`](../../app/Shared/Auth/AuthContext.php) | Wrapper quanh `auth()` — lấy user/id hiện tại |
| `TenantScope` | [`app/Models/Scopes/TenantScope.php`](../../app/Models/Scopes/TenantScope.php) | Global scope — lọc theo user đang login (không phải tenant_id trực tiếp, xem [01-request-lifecycle.md](./01-request-lifecycle.md)) |
| `chooseTenant` middleware | [`ChooseCurrentTenant.php`](../../app/Http/Middleware/ChooseCurrentTenant.php) | Bắt buộc phải có tenant trong session, nếu không → 403 |

---

## ⚠️ Điểm cần lưu ý khi đọc code (đã verify trong repo)

1. **`TenantScope` áp dụng cho model `Tenant`** (lọc các tenant mà user hiện tại thuộc về), KHÔNG phải lọc `Project`/`Task` theo `tenant_id`. Các model con (Project, Task, User...) được scope theo `tenant_id` tường minh qua repository (`tenantContext()->getId()` truyền vào UseCase).
2. **`notifySystem()` pattern** trong UseCase (Task, Tenant) gọi `authContext()->getUser()->name` trực tiếp — đây là deviation so với rule "UseCase không đọc session/context", đã được team chỉnh sửa trực tiếp trong code thực tế (xem ghi chú ở [02-feature-crud-flow.md](./02-feature-crud-flow.md)).
3. **3 hệ thống side-effect (Audit / Mail / Notification) đều queue-based** — không block response. Cùng dùng pattern: Interface trong Application layer → bind trong `AppServiceProvider` → impl trong Infrastructure → dispatch Job.
