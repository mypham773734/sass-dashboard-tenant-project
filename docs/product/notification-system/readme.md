# Notification System — Entry Point

**Feature:** In-app notifications — real-time alerts cho user ngay trong dashboard  
**Status:** Planning  
**Last Updated:** 2026-06-09

---

## Problem

Hiện tại hệ thống chỉ có Mail Service để thông báo ra ngoài app. Khi xảy ra sự kiện quan trọng trong tenant:

- User không biết mình vừa được assign task mới
- Admin không biết có member mới vào workspace
- Owner không thấy có thay đổi permission trong khi đang làm việc
- Phải vào Audit Log mới biết có gì xảy ra — quá chậm, không thân thiện

**Vấn đề cốt lõi:** Không có kênh feedback real-time trong giao diện. Mail chỉ dùng được khi user tắt app. Cần một lớp thông báo **ngay bên trong dashboard**.

---

## Solution

**In-app Notification System:** Một bell icon trên header, click ra dropdown danh sách thông báo, có badge đếm unread. Thông báo được ghi vào DB khi có sự kiện, user thấy ngay khi load trang hoặc polling.

```
Domain Event / UseCase
        ↓
NotificationServiceInterface
        ↓
WriteNotificationJob (queue)
        ↓
notifications table (per user, per tenant)
        ↓
Livewire NotificationBell (polling / SSE)
        ↓
User thấy badge + dropdown
```

**Nguyên tắc thiết kế:**
- Notification là **append-only** (giống audit log) — không edit, chỉ mark-read hoặc delete
- **Multi-tenant scoped** — user chỉ thấy notification của tenant đang active
- **Decoupled** — UseCase gọi `NotificationServiceInterface`, không biết về transport
- **Config-driven** — từng event type có thể bật/tắt qua config

---

## Phân biệt với Mail Service

| | Mail Service | Notification System |
|---|---|---|
| **Kênh** | Email (ra ngoài app) | In-app (bên trong dashboard) |
| **Khi nào dùng** | User offline, quan trọng, cần record | User online, cần biết ngay |
| **Lưu trữ** | Không lưu (chỉ gửi) | Lưu vào DB, có read/unread state |
| **Recipients** | Theo role tenant | Theo từng user cụ thể |
| **Livewire** | Không | Có (NotificationBell component) |

Hai hệ thống hoạt động **song song** — cùng một sự kiện có thể trigger cả hai.

---

## Reading Order

1. **[01-requirements.md](./01-requirements.md)** — Event types, data model, functional requirements
2. **[02-architecture.md](./02-architecture.md)** — Layer design, class diagram, data flow
3. **[03-implementation-plan.md](./03-implementation-plan.md)** — Phases, file checklist, timeline

---

## Quick Reference

### Event types (MVP)

| Event | Recipients | Priority |
|---|---|---|
| `task.assigned` | User được assign | High |
| `task.status_changed` | Creator + Assignee | Medium |
| `tenant.member_added` | Owner + Admins | Medium |
| `tenant.member_removed` | Owner + Admins | High |
| `tenant.role_changed` | User bị đổi role | High |
| `project.created` | Owner + Admins | Low |
| `mention` | User được mention | High |

### Core API (dự kiến)

```php
// Trong UseCase
$this->notificationService->notify(
    event:    'task.assigned',
    tenantId: $tenantId,
    recipients: [$assigneeUserId],
    context:  ['task_title' => $task->title, 'task_url' => route(...)],
);
```

---

## Success Criteria

- [ ] Bell icon hiển thị badge số unread
- [ ] Dropdown hiển thị tối đa 10 notification gần nhất
- [ ] Click notification → navigate đến đúng resource
- [ ] Mark as read (single + mark all read)
- [ ] Notification scoped đúng tenant đang active
- [ ] Ghi notification không làm chậm HTTP request (async queue)
- [ ] Có thể bật/tắt từng event type qua config
