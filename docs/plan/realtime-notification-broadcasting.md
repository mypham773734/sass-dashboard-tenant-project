# Plan: Real-time Notification System (Event Broadcasting)

**📌 This plan has been developed into detailed product docs:**  
**→ See [docs/product/realtime-broadcasting/](../product/realtime-broadcasting/) for implementation-ready specs**

---

## Context

Notification system hiện tại đã hoàn chỉnh về backend (handler config-driven, `WriteNotificationJob` ghi DB qua queue) nhưng UI chỉ cập nhật qua `wire:poll.5s` trên Livewire `NotificationBell` — không phải real-time thật. Mục tiêu: enhance lên real-time bằng Laravel Event Broadcasting.

**Yêu cầu:**
- Docs so sánh cả 2 driver: Laravel Reverb vs Pusher (quyết định driver sau)
- Thiết kế hạ tầng broadcasting làm **nền tảng tái dùng cho chat realtime** sau này (đã có trong `plans/plan.md`)

**Hiện trạng (đã khảo sát):**
- Chưa có gì về broadcasting: không có `config/broadcasting.php`, `routes/channels.php`, laravel-echo, pusher-js; `BROADCAST_CONNECTION=log`
- `NotificationBell.php` đã có listener `notification-added` (chưa được fire)
- Multi-tenant: mọi notification scoped theo `tenant_id` + `user_id`, tenant hiện tại lấy từ `tenantContext()` (session-based, có middleware ChooseCurrentTenant, pivot `tenant_user`)
- Docs hiện có theo convention: `docs/product/notification-system/` (readme, 01-requirements, 02-architecture, 03-implementation-plan) — file/folder lowercase kebab-case

## Deliverables (docs design, chưa code)

Tạo folder mới `docs/product/realtime-broadcasting/` (vì là hạ tầng dùng chung cho notification + chat sau này, không chỉ riêng notification-system), theo đúng convention 4 file:

### 1. `docs/product/realtime-broadcasting/readme.md`
- Tổng quan: vấn đề (polling 5s), giải pháp (WebSocket qua Laravel Broadcasting + Echo)
- Sơ đồ flow: UseCase → NotificationService → WriteNotificationJob → DB save → broadcast `NotificationCreated` → Reverb/Pusher → Echo → Livewire cập nhật bell ngay lập tức
- Phạm vi: Phase 1 = notification bell real-time (bỏ polling); nền tảng cho chat realtime (Phase sau)

### 2. `docs/product/realtime-broadcasting/01-requirements.md`
- Functional: notification mới hiện trên bell ngay (< 1s) không cần reload/poll; badge count cập nhật; chỉ user nhận notification trong đúng tenant mới thấy
- Non-functional: tenant isolation tuyệt đối ở channel level; fallback khi WebSocket chết (giữ polling làm fallback hoặc reconnect của Echo); queue-based, không block request
- **So sánh driver Reverb vs Pusher** (bảng): chi phí (Reverb free self-hosted vs Pusher free tier 200k msg/100 conn), vận hành (chạy `reverb:start` + supervisor vs managed), scale, dev local trên Windows; kết luận: code/kiến trúc giống hệt nhau (cùng Pusher protocol), chỉ khác `.env` — khuyến nghị Reverb cho dev, quyết định production sau
- Out of scope: push notification trình duyệt, email, toast

### 3. `docs/product/realtime-broadcasting/02-architecture.md`
Phần quan trọng nhất — thiết kế chi tiết:

**Channel design (tái dùng cho chat):**
- `private-tenant.{tenantId}.user.{userId}` — notification cá nhân (Phase 1)
- Dự phòng cho chat: `private-tenant.{tenantId}.conversation.{conversationId}` + `presence-tenant.{tenantId}` (online status)
- Channel authorization trong `routes/channels.php`: verify user đăng nhập đúng `userId` VÀ thuộc tenant (check pivot `tenant_user`) — đây là lớp tenant-isolation
- Lưu ý tenant switching: khi đổi tenant qua ChooseCurrentTenant, frontend phải unsubscribe/resubscribe channel theo tenant mới

**Backend (đặt đúng layer Clean Architecture):**
- Broadcast event `NotificationCreated` (implements `ShouldBroadcast`) đặt ở **Infrastructure layer** (`app/Infrastructure/Notifications/Events/`) vì phụ thuộc Laravel — Domain/Application không đổi
- Fire từ `WriteNotificationJob` sau khi save DB thành công (đã trong queue context, không block)
- Payload: id, title, body, url, createdAt (đủ để render item mà không cần query lại) hoặc chỉ signal "có mới" để Livewire tự refresh từ DB (khuyến nghị cách signal — đơn giản, nhất quán với repository pattern, tránh lộ data qua nhiều kênh)
- Broadcast qua queue (`ShouldBroadcast` mặc định queued) — dùng chung queue worker hiện có

**Frontend:**
- Cài `laravel-echo` + `pusher-js` (npm), tạo `resources/js/echo.js`, import trong `bootstrap.js`
- Livewire 4: `NotificationBell` listen qua attribute `#[On('echo-private:tenant.{tenantId}.user.{userId},NotificationCreated')]` → gọi `refresh()`; bỏ `wire:poll.5s` (hoặc giữ poll 60s làm fallback)
- Cần truyền tenantId/userId vào component để build tên channel động

**Config cần thiết:** `php artisan install:broadcasting` (tạo config/broadcasting.php, routes/channels.php, echo.js), biến `.env` cho cả 2 driver

### 4. `docs/product/realtime-broadcasting/03-implementation-plan.md`
Checklist từng phase (để thực thi sau):
- Phase 1: Cài đặt hạ tầng (install:broadcasting, chọn driver dev = Reverb, npm packages)
- Phase 2: Backend (channels.php auth, NotificationCreated event, fire từ WriteNotificationJob, test bằng tinker)
- Phase 3: Frontend (echo.js, Livewire listener, bỏ/giảm polling)
- Phase 4: Verification (2 browser/2 user, đổi tenant, tắt Reverb xem fallback) + estimate effort mỗi phase

### 5. Cập nhật docs hiện có (2 sửa nhỏ)
- `docs/product/notification-system/readme.md`: thêm link sang `realtime-broadcasting/` (real-time không còn "out of scope")
- `plans/plan.md`: đánh dấu/ghi chú mục notification real-time đã có design docs

## Verification
- Đọc lại các file docs: đúng naming convention (lowercase kebab-case), link tương đối hoạt động
- Nội dung khớp hiện trạng code (tên file, class thật: `WriteNotificationJob`, `NotificationBell`, `tenantContext()`)
- Không có file code nào bị sửa (chỉ `.md`)
