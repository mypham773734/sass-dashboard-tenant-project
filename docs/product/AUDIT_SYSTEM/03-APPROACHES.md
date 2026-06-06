# Audit System — Approaches

---
Version: 1.1
Last Updated: 2026-06-06
Status: Approved
Author: Architecture Team
---

## Bốn Approaches Được Xem Xét

---

## Approach A — Laravel Package (owen-it/laravel-auditing)

### Mô tả

Cài package `owen-it/laravel-auditing`, thêm `Auditable` trait vào Eloquent models.

```php
class Task extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
}
```

### Pros
- Zero code — chỉ add trait
- Tự động track mọi field change (diff chi tiết)
- Có sẵn: `old_values`, `new_values`, IP, user

### Cons
- Không biết multi-tenant — phải custom thêm `tenant_id`
- Audit dựa trên Eloquent model events → Infrastructure detail leak vào Domain
- Không audit được: auth events, permission changes
- Seeder/migration cũng fire → audit noise
- Khó test — phụ thuộc vào Model events

---

## Approach B — Eloquent Model Observers

### Mô tả

Tạo `TaskObserver`, `ProjectObserver` lắng nghe model events (`created`, `updated`, `deleted`).

```php
class TaskObserver
{
    public function created(Task $task): void
    {
        AuditLogger::log('task.created', $task->tenant_id, ...);
    }
}
```

### Pros
- Không cần package ngoài
- Laravel native

### Cons
- Observer gắn vào Eloquent Model → vi phạm Clean Architecture
- Không biết business intent — chỉ biết DB thay đổi, không biết tại sao
- Seeder/migration fire observer → audit noise
- Không audit được auth events, permission changes

---

## Approach C — Domain Events + Event Listener

### Mô tả

Use Cases dispatch Domain Events. Event Listener bắt và ghi audit log qua Queue.

```php
// CreateTaskUseCase
event(new TaskCreatedEvent($task, $tenantId, $userId));

// AuditEventListener
public function handleTaskCreated(TaskCreatedEvent $event): void
{
    WriteAuditLogJob::dispatch($event->toAuditLog());
}
```

### Pros
- Fit Clean Architecture
- Explicit intent — Use Case kiểm soát context
- Async qua Queue
- Auth events miễn phí (Laravel built-in)

### Cons — **Vấn đề ở scale**

Domain Events giải quyết bài toán: **"1 action → NHIỀU side effects ở nhiều domain."**

Audit Log là **1 side effect duy nhất, đồng nhất**. Dùng Events ở đây tạo boilerplate không có business value:

```
10 entities × 4 CRUD = 40 Event classes
                      + 40 handler methods
                      + 40 EventServiceProvider registrations
```

Thêm entity mới → phải tạo 4 Event classes + đăng ký = **không scale tốt**.

---

## Approach D — AuditLogger Service (Recommended ✅)

### Mô tả

Inject `AuditLoggerInterface` trực tiếp vào Use Case. Async được xử lý **bên trong** implementation — caller không biết và không cần biết.

```php
class CreateTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $repo,
        private AuditLoggerInterface    $audit,  // ← inject
    ) {}

    public function execute(CreateTaskDTO $dto, int $tenantId, int $userId): TaskEntity
    {
        $task = $this->repo->create($dto, $tenantId, $userId);

        $this->audit->log('task.created', $task->id, 'Task', [
            'title' => $task->title,
        ]);

        return $task;
    }
}
```

`QueuedAuditLogger` (Infrastructure) tự dispatch Queue Job bên trong — Use Case không biết có queue hay không.

### Pros

| | |
|---|---|
| ✅ | **Scale tốt** — thêm entity mới chỉ cần 1 dòng `$this->audit->log()` |
| ✅ | **Không boilerplate** — không cần Event class, handler, EventServiceProvider |
| ✅ | **Clean Architecture** — interface trong Application layer, implementation trong Infrastructure |
| ✅ | **Async** — xử lý bên trong QueuedAuditLogger, transparent với caller |
| ✅ | **Testable** — mock `AuditLoggerInterface` hoặc dùng in-memory implementation |
| ✅ | **Intent capture** — Use Case truyền đúng context cần audit |
| ✅ | **Auth events miễn phí** — vẫn dùng Laravel built-in events |

### Cons

| | |
|---|---|
| ⚠️ | Mỗi Use Case phải inject thêm 1 dependency (`AuditLoggerInterface`) |
| ⚠️ | `session()` và `request()` được gọi bên trong `QueuedAuditLogger` → cần đảm bảo context available khi job được dispatch (không phải khi job chạy) |

### Fix cho con ⚠️ thứ 2

Context phải được capture **tại thời điểm dispatch**, không phải khi job execute:

```php
class QueuedAuditLogger implements AuditLoggerInterface
{
    public function log(string $action, ...): void
    {
        WriteAuditLogJob::dispatch([
            'tenant_id'  => session('current_tenant_id'),  // capture ngay
            'user_id'    => auth()->id(),                  // capture ngay
            'ip_address' => request()->ip(),               // capture ngay
            'action'     => $action,
            ...
        ]);
    }
}
```

Job chỉ nhận array data — không access session/request.

---

## So Sánh Tổng Hợp

| Tiêu chí | A — Package | B — Observer | C — Domain Events | D — AuditLogger ✅ |
|---|---|---|---|---|
| **Clean Architecture** | ❌ | ⚠️ | ✅ | ✅ |
| **Multi-tenant** | ❌ Manual | ❌ Manual | ✅ | ✅ |
| **Auth events** | ❌ | ❌ | ✅ | ✅ (hybrid) |
| **Permission events** | ❌ | ❌ | ✅ | ✅ |
| **Async** | ⚠️ | ❌ | ✅ | ✅ |
| **Testability** | ❌ | ⚠️ | ✅ | ✅ |
| **Scale (thêm entity mới)** | ✅ Auto | ✅ Auto | ❌ 4 file/entity | ✅ 1 dòng/entity |
| **Boilerplate** | 🟢 Thấp | 🟢 Thấp | 🔴 Cao | 🟢 Thấp |
| **Seeder noise** | ❌ | ❌ | ✅ | ✅ |
| **Intent capture** | ❌ | ❌ | ✅ | ✅ |
| **Setup effort** | 🟢 Thấp | 🟢 Thấp | 🟡 Cao | 🟡 Trung bình |

---

## Decision: Approach D — AuditLogger Service (Hybrid)

### Chiến lược

```
CRUD Use Cases    → inject AuditLoggerInterface → log() trực tiếp
Auth events       → Laravel built-in events (Illuminate\Auth\Events\*)
Permission events → AuditLogger::log() tại điểm gán role
```

### Lý do không chọn C ở scale

Domain Events phù hợp khi **1 action cần kích hoạt nhiều side effects ở nhiều domain khác nhau**:

```
OrderPlaced → SendEmailListener
           → UpdateInventoryListener
           → NotifyWarehouseListener   ← nhiều listeners, nhiều domain
```

Audit Log là **1 side effect duy nhất** → Events chỉ thêm indirection không có giá trị:

```
TaskCreatedEvent → AuditEventListener → WriteAuditLogJob
```

Có thể rút gọn thành:

```
CreateTaskUseCase → AuditLogger → WriteAuditLogJob
```

Ít layer hơn, cùng kết quả, dễ maintain hơn.

### Khi nào nên dùng Domain Events cho audit?

Khi có **nhiều listeners** cùng lắng nghe 1 event — ví dụ tương lai nếu muốn:
- `TaskCreatedEvent` → `AuditEventListener` + `NotificationListener` + `WebhookListener`

Lúc đó Domain Events có giá trị vì decouple multiple consumers. Với audit-only, không cần thiết.

---

## Related Documents

- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — Chi tiết design Approach D
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Build steps
