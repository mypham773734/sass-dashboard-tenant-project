# Mail Service — Requirements

**Status:** Draft  
**Last Updated:** 2026-06-09

---

## Email Types (MVP)

### 1. User Invitation (On-demand)

| Field | Value |
|---|---|
| Trigger | Admin mời user vào tenant |
| Khi nào | Ngay lập tức khi `InviteUserUseCase` chạy |
| Recipients | Email address của người được mời |
| Handler | `UserInvitationHandler` |
| Context cần | `invited_email`, `invited_by`, `tenant_name`, `accept_url` |

### 2. Tenant Notification (On-demand)

| Field | Value |
|---|---|
| Trigger | Sự kiện quan trọng trong tenant (user bị xóa, role thay đổi...) |
| Khi nào | Ngay lập tức |
| Recipients | Owner + Admin của tenant (resolve từ DB lúc runtime) |
| Handler | `TenantNotificationHandler` |
| Context cần | `event_type`, `description`, `actor_name` |

### 3. Audit Digest (Scheduled)

| Field | Value |
|---|---|
| Trigger | Cron job hàng ngày |
| Schedule | Configurable qua env (`AUDIT_DIGEST_SCHEDULE=daily_08_00`) |
| Recipients | Owner + Admin của **từng** tenant (resolve từ DB lúc runtime) |
| Handler | `AuditDigestHandler` |
| Context cần | `date`, `tenant_id` (handler tự query `audit_logs`) |

> **Lưu ý multi-tenant:** Recipients không hardcode trong config — Handler có trách nhiệm tự resolve danh sách email của owner/admin thuộc về `$tenantId` từ DB.

---

## MailServiceInterface API

Use Cases inject interface này — không biết concrete implementation:

```php
interface MailServiceInterface
{
    // Gửi async qua queue (khuyến khích dùng)
    public function dispatch(string $type, int $tenantId, array $context = []): void;

    // Gửi sync (immediate, dùng khi cần chắc chắn gửi xong trước khi tiếp tục)
    public function send(string $type, int $tenantId, array $context = []): void;

    // Gọi bởi scheduled command — dispatch tất cả email types có schedule
    public function dispatchScheduled(Carbon $now): void;
}
```

---

## EmailHandlerInterface

Mỗi email type có 1 Handler:

```php
interface EmailHandlerInterface
{
    // Nhận tenantId + context → trả về EmailDTO chứa subject, recipients, template data
    public function handle(int $tenantId, array $context): EmailDTO;

    // Kiểm tra có nên chạy tại thời điểm $now không (dùng cho scheduled emails)
    public function shouldSend(string $schedule, Carbon $now): bool;
}
```

---

## EmailDTO

```php
class EmailDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $subject,
        public readonly array  $recipients,   // ['email@example.com', ...]
        public readonly string $template,     // 'emails.user-invitation'
        public readonly array  $data = [],    // passed to Blade template
    ) {}
}
```

---

## Config Structure

```php
// config/mail-service.php
return [
    'enabled' => env('MAIL_SERVICE_ENABLED', true),
    'queue'   => env('MAIL_SERVICE_QUEUE', 'mail'),

    'email_types' => [

        'user_invitation' => [
            'enabled'  => env('USER_INVITATION_ENABLED', true),
            'handler'  => \App\Infrastructure\Mail\Handlers\UserInvitationHandler::class,
            'template' => 'emails.user-invitation',
            // Không có 'schedule' → on-demand only
        ],

        'tenant_notification' => [
            'enabled'  => env('TENANT_NOTIFICATION_ENABLED', true),
            'handler'  => \App\Infrastructure\Mail\Handlers\TenantNotificationHandler::class,
            'template' => 'emails.tenant-notification',
        ],

        'audit_digest' => [
            'enabled'  => env('AUDIT_DIGEST_ENABLED', true),
            'schedule' => env('AUDIT_DIGEST_SCHEDULE', 'daily_08_00'),
            'handler'  => \App\Infrastructure\Mail\Handlers\AuditDigestHandler::class,
            'template' => 'emails.audit-digest',
        ],

    ],
];
```

---

## Functional Requirements

| ID | Requirement |
|---|---|
| FR1 | Config-based email types — không cần migration để thêm type mới |
| FR2 | Mỗi email type có Handler riêng implement `EmailHandlerInterface` |
| FR3 | On-demand dispatch qua `MailServiceInterface::dispatch()` từ Use Case |
| FR4 | Scheduled dispatch qua Artisan command (`mail:send-scheduled`) |
| FR5 | Gửi async qua Laravel queue — không block HTTP request |
| FR6 | Mọi lần gửi mail được log vào `audit_logs` (action: `mail.sent`) |
| FR7 | Recipients được resolve lúc runtime từ DB — không hardcode trong config |
| FR8 | Thêm email type mới không cần sửa core (`MailService`, `SendMailJob`) |
| FR9 | Use Cases inject `MailServiceInterface` — không phụ thuộc Infrastructure |

---

## Non-Functional Requirements

| Requirement | Target |
|---|---|
| Latency impact | Dispatch < 5ms — async, không block request |
| Retry on failure | 3 retries, backoff 60s (Laravel queue default) |
| Failed job handling | Log to `failed_jobs` table, alert via log channel |
| Rate limiting | Không gửi quá 100 emails/phút (configurable) |
| Test isolation | `NullMailService` cho test — không gửi mail thật, lưu in-memory |
| Multi-tenant | Handler luôn nhận `$tenantId`, không được truy cập tenant khác |

---

## Constraints

- Không dùng package ngoài (laravel-mailcoach, mailgun SDK...) — dùng Laravel Mail facade
- Fit Clean Architecture: Interface ở Application, Implementation ở Infrastructure
- `MailServiceInterface` phải được bind trong `AppServiceProvider`
- Handlers không được gọi `session()`, `auth()`, `request()` — nhận context qua `$context` array

---

## Success Criteria (Testable)

- [ ] `InviteUserUseCase` gọi `$mail->dispatch('user_invitation', ...)` → job được queue
- [ ] `SendMailJob` gọi đúng handler → render template → gửi mail → log audit `mail.sent`
- [ ] `mail:send-scheduled` chỉ dispatch các type có `schedule` và đúng thời điểm
- [ ] `AuditDigestHandler` lấy recipients từ DB (owner/admin của tenant), không hardcode
- [ ] Test với `NullMailService`: `assertSent('user_invitation')` pass, không gửi mail thật
- [ ] Sai email type → `InvalidArgumentException`, không fail silently
- [ ] Mail disabled (`MAIL_SERVICE_ENABLED=false`) → không gửi, không throw exception
