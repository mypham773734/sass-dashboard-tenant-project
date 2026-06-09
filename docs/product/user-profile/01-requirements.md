# User Profile — Requirements

---
Version: 1.0
Last Updated: 2026-06-06
Status: Draft
Author: Product Team
---

## Functional Requirements

### FR1: Update Basic Info

User có thể cập nhật thông tin cá nhân:

| Field | Rules |
|---|---|
| `name` | Required, min 2, max 100 ký tự |
| `email` | Required, valid email, unique trong bảng users (trừ chính mình) |
| `phone` | Optional, nullable, max 20 ký tự |

**Rules:**
- Chỉ tác động lên `auth()->user()` — không nhận `user_id` từ request
- Email đổi không yêu cầu re-verification (v1)
- Submit form → flash success/error → redirect lại profile page

---

### FR2: Upload Avatar

User có thể upload ảnh đại diện:

| Constraint | Value |
|---|---|
| Allowed MIME | `image/jpeg`, `image/png`, `image/webp` |
| Max file size | 2 MB |
| Stored at | `storage/app/public/avatars/{userId}.{ext}` |
| Symlink | `php artisan storage:link` phải chạy trước |
| Fallback | Initials avatar nếu không có ảnh (CSS-generated) |

**Rules:**
- Upload cùng form với basic info (1 request)
- Nếu đã có avatar cũ → xoá file cũ trước khi lưu file mới
- Filename dùng `userId` để tránh trùng và tránh race conditions

---

### FR3: Change Password

User có thể đổi password với form riêng (section riêng trong cùng trang):

| Field | Rules |
|---|---|
| `current_password` | Required, phải khớp với password hiện tại |
| `new_password` | Required, min 8 ký tự, có chữ hoa + số |
| `new_password_confirmation` | Required, phải khớp với `new_password` |

**Rules:**
- Sai `current_password` → validation error "Current password is incorrect"
- Đổi password thành công → **logout tất cả sessions khác** (invalidate remember tokens)
- Không cần logout session hiện tại
- Ghi audit log: `profile.password_changed`

---

### FR4: Tenant Memberships (Read-only)

User thấy danh sách các tenants mình đang tham gia:

| Column | Data |
|---|---|
| Tenant name | `tenants.name` |
| Slug | `tenants.slug` |
| Role | Role name của user trong tenant đó |
| Status | Active / Inactive |
| Joined at | `tenant_user.created_at` (nếu có) |

**Rules:**
- Read-only — không thể sửa từ trang này
- "Switch tenant" button → cập nhật `session('current_tenant_id')`

---

### FR5: Sidebar Avatar Update

Sau khi update avatar hoặc name, sidebar phải hiển thị đúng:

- Avatar: dùng `<img src="{{ auth()->user()->avatar_url }}">` hoặc initials fallback
- Name: hiển thị đúng name hiện tại của user (không hardcode "JD")

---

### FR6: Audit Log Integration

Mọi thay đổi profile ghi vào audit log (dùng `AuditLoggerInterface` đã có):

| Action | Trigger | old_values | new_values |
|---|---|---|---|
| `profile.updated` | Cập nhật name/email/phone/avatar | name, email, phone | name, email, phone |
| `profile.password_changed` | Đổi password thành công | null | null (không log password) |

---

## Non-Functional Requirements

### Security
- **Chỉ user tự sửa mình**: không nhận `user_id` từ URL hay form — dùng `auth()->user()` trực tiếp
- **Avatar**: validate MIME type server-side (không tin vào file extension)
- **Password**: hash bằng `bcrypt` (Laravel default), không bao giờ log
- **File upload**: sanitize filename, không allow path traversal

### Performance
- Avatar resize không cần trong v1 (upload nguyên file, max 2MB là đủ)
- Profile page: < 200ms response (không có heavy query)

### UX
- Form errors hiển thị inline bên cạnh field
- Success message sau khi save
- Avatar preview trước khi upload (JavaScript optional)

---

## Current DB State

### users table (không thay đổi)

```
users:
  id                  ✅ có
  name                ✅ có
  email               ✅ có (unique)
  email_verified_at   ✅ có
  password            ✅ có
  remember_token      ✅ có
  timestamps          ✅ có
```

**users table giữ nguyên** — không thêm column nào.

### user_meta table (cần tạo mới)

```
user_meta:
  id         — PK auto-increment
  user_id    — FK → users.id (CASCADE DELETE)
  key        — varchar, e.g. 'avatar', 'phone', 'bio', 'timezone'
  value      — text, nullable
  timestamps — created_at, updated_at
```

**Quan hệ:** `users` ONE-TO-MANY `user_meta` (một user có nhiều meta records, mỗi record là 1 key-value).

**Keys đang dùng trong v1:**

| Key | Mô tả | Example value |
|---|---|---|
| `avatar` | Path file ảnh đại diện | `avatars/1.jpg` |
| `phone` | Số điện thoại | `0901234567` |

**Lợi thế của user_meta:**
- Thêm field mới (bio, timezone, social links) chỉ cần insert row — không cần migration
- Bảng `users` sạch — chỉ chứa auth data
- Extensible: mỗi tenant có thể require các meta keys khác nhau trong tương lai

Cần 1 migration tạo bảng `user_meta`.

---

## Related Documents

- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — System design, diagrams
- [03-APPROACHES.md](./03-APPROACHES.md) — Các lựa chọn thiết kế
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Build steps
