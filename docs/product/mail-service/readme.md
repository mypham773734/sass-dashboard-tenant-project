# Mail Service

**Status:** Draft — Pending review  
**Last Updated:** 2026-06-09

---

## Problem

Hệ thống cần gửi email cho nhiều mục đích (mời user, thông báo tenant, digest audit log) nhưng chưa có cơ chế thống nhất. Mỗi feature tự gửi mail theo cách riêng → không kiểm soát được, khó test, khó mở rộng.

## Solution

**Mail Service** — một lớp gửi email tập trung, config-driven, pluggable handler per email type.  
Inject qua `MailServiceInterface` (Application layer) — đúng Clean Architecture, testable, không phụ thuộc vào Infrastructure.

```
Use Case → MailServiceInterface → (bound to) MailService → SendMailJob → Queue → Mail facade
```

**Nguyên tắc cốt lõi:**
- **Interface-first:** Use Cases inject `MailServiceInterface`, không biết concrete implementation
- **Config-driven:** Mỗi email type có config riêng (`config/mail-service.php`) — không cần migration
- **Pluggable:** Thêm email type mới = tạo Handler + cập nhật config — không sửa core
- **Async:** Gửi qua queue, không block HTTP request
- **Multi-tenant:** Recipients resolve lúc runtime (owner/admin của tenant), không hardcode
- **Auditable:** Mọi lần gửi mail được log vào `audit_logs`

---

## Design Decision: Tại sao dùng Interface?

Giống `AuditLoggerInterface` trong Audit System — Use Cases không được phụ thuộc trực tiếp vào Infrastructure:

```php
// ❌ Sai — Use Case phụ thuộc Infrastructure
class InviteUserUseCase {
    public function __construct(private MailService $mail) {}
}

// ✅ Đúng — Use Case phụ thuộc Application contract
class InviteUserUseCase {
    public function __construct(private MailServiceInterface $mail) {}
}
```

Trong tests, bind `NullMailService` thay vì `MailService` → không gửi mail thật, kiểm tra được `assertSent()`.

---

## Reading Order

1. **[01-requirements.md](./01-requirements.md)** — Email types, functional & non-functional requirements
2. **[02-architecture.md](./02-architecture.md)** — Layer mapping, diagrams, class design, data flow
3. **[03-implementation_plan.md](./03-implementation_plan.md)** — Phases, file checklist, usage examples

---

## Quick Reference

### Gửi email từ Use Case

```php
class InviteUserUseCase
{
    public function __construct(
        private readonly MailServiceInterface $mail,
    ) {}

    public function execute(InviteUserDTO $dto, int $tenantId): void
    {
        // ... business logic ...

        $this->mail->dispatch('user_invitation', $tenantId, [
            'invited_email' => $dto->email,
            'invited_by'    => $dto->inviterName,
        ]);
    }
}
```

### Thêm email type mới (3 bước)

**Bước 1** — Thêm vào `config/mail-service.php`:
```php
'weekly_report' => [
    'enabled'  => true,
    'schedule' => 'weekly_friday_09_00',
    'handler'  => WeeklyReportHandler::class,
    'template' => 'emails.weekly-report',
],
```

**Bước 2** — Tạo Handler (`implements EmailHandlerInterface`):
```php
class WeeklyReportHandler implements EmailHandlerInterface {
    public function handle(int $tenantId, array $context): EmailDTO { ... }
    public function shouldSend(string $schedule, Carbon $now): bool { ... }
}
```

**Bước 3** — Tạo Mailable + Blade template.

Không cần sửa gì khác — `MailService` tự resolve handler từ config qua `app($handlerClass)`.

---

## Config

```env
MAIL_SERVICE_ENABLED=true
MAIL_SERVICE_QUEUE=mail
AUDIT_DIGEST_ENABLED=true
AUDIT_DIGEST_SCHEDULE=daily_08_00
```

---

## Related

- **[AUDIT_EMAIL](../audit-email/readme.md)** — Phase 2: per-tenant email config lưu DB (UI cho admin tự cấu hình). Blocked by MAIL_SERVICE MVP.
- **Audit System** (`docs/product/audit-system/`) — `audit_logs` table được dùng bởi `AuditDigestHandler`
