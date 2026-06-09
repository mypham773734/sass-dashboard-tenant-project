# User Profile — Approaches

---
Version: 1.0
Last Updated: 2026-06-06
Status: Approved
Author: Architecture Team
---

## Các quyết định thiết kế quan trọng

Profile page có 4 câu hỏi thiết kế cần quyết định trước khi code.

---

## Câu hỏi 1: Lưu avatar ở đâu?

### Option A — Local Storage (public disk)
Lưu file vào `storage/app/public/avatars/` → serve qua `public/storage/` symlink.

**Pros:** Không cần service ngoài, setup đơn giản, chi phí zero.
**Cons:** Không scale khi multi-server (mỗi server có storage riêng).

### Option B — Cloud Storage (S3 / R2 / GCS)
Dùng `Storage::disk('s3')` → file lên cloud.

**Pros:** Scale tốt, CDN, không lo disk space.
**Cons:** Cần config credentials, phức tạp hơn cho dev, tốn tiền.

### Option C — Base64 trong DB
Encode ảnh thành base64 → lưu vào column `avatar TEXT`.

**Pros:** Không cần file system.
**Cons:** DB phình to, performance kém, anti-pattern.

**→ Chọn A (Local Storage) cho v1.** Multi-server là bài toán tương lai, có thể đổi driver từ `local` sang `s3` chỉ bằng config khi cần.

---

## Câu hỏi 2: ProfileController hay mở rộng UserController?

### Option A — ProfileController riêng biệt ✅
Tạo `ProfileController` mới, route `/profile/*`.

```
ProfileController → quản lý auth()->user() chỉ
UserController    → admin quản lý tất cả users (khác người)
```

**Pros:** Single Responsibility rõ ràng, dễ test, dễ phân quyền.
**Cons:** Thêm 1 file controller.

### Option B — Thêm method vào UserController hiện tại
Thêm `profile()`, `updateProfile()` vào `UserController`.

**Pros:** Ít file hơn.
**Cons:** UserController đang là admin controller (manage other users) — profile của chính mình là concern khác, nhầm lẫn ngữ nghĩa. Vi phạm SRP.

**→ Chọn A.** `UserController` = admin quản lý users. `ProfileController` = user tự quản lý mình.

---

## Câu hỏi 3: Có cần Domain\User\UserEntity không?

### Option A — Tạo UserEntity trong Domain ✅
Đúng Clean Architecture: `Domain/User/Entities/UserEntity.php`.

**Pros:** Consistent với TaskEntity, ProjectEntity, TenantEntity. Repository pattern rõ ràng.
**Cons:** Thêm 1 entity class + mapping code.

### Option B — Dùng Eloquent User model trực tiếp trong Use Case
Use Case nhận `User` model từ `auth()->user()`.

**Pros:** Ít code hơn.
**Cons:** Vi phạm Clean Architecture — Application layer phụ thuộc vào Infrastructure (Eloquent). Không testable độc lập.

**→ Chọn A.** Project đã có pattern rõ ràng với Task/Project — profile phải consistent.

---

## Câu hỏi 4: Đổi email có cần re-verify không?

### Option A — Không cần verify (v1) ✅
Đổi email → lưu ngay, không cần confirm qua link.

**Pros:** Đơn giản, ít bước.
**Cons:** Có thể user nhập sai email và mất access (nếu login bằng email).

### Option B — Verify email mới trước khi apply
Gửi link xác nhận đến email mới → sau khi click mới cập nhật.

**Pros:** An toàn hơn.
**Cons:** Phức tạp hơn nhiều (cần queue mail, signed URL, temporary storage).

**→ Chọn A cho v1.** Thêm warning UI "Hãy chắc chắn email đúng trước khi lưu". V2 có thể thêm verify flow.

---

## Câu hỏi 5: Lưu avatar và phone ở đâu trong DB?

Profile data như `avatar` và `phone` không phải auth data — chúng là metadata của user. Có 2 lựa chọn:

### Option A — Thêm column trực tiếp vào bảng `users`

```sql
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN phone  VARCHAR(20) NULL;
```

**Pros:**
- Đơn giản, query trực tiếp `$user->avatar`
- Ít abstraction, migration nhanh

**Cons:**
- Mỗi field mới (bio, timezone, social_url) = 1 migration mới
- Bảng `users` phình ra — lẫn auth data và profile data
- Không thể thêm field động, cột nullable nhiều → data sparse

### Option B — Bảng `user_meta` key-value (one-to-many) ✅

```sql
CREATE TABLE user_meta (
    id       BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id  BIGINT UNSIGNED NOT NULL,
    key      VARCHAR(100) NOT NULL,   -- 'avatar', 'phone', 'bio'
    value    TEXT NULL,
    created_at TIMESTAMP, updated_at TIMESTAMP,
    UNIQUE KEY uq_user_meta (user_id, key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Pros:**
- Thêm field mới chỉ cần insert row — **không cần migration**
- Bảng `users` sạch — chỉ auth data (id, name, email, password)
- Extensible: từng tenant có thể require các meta keys khác nhau về sau
- Pattern phổ biến: WordPress `usermeta`, Drupal fields, Laravel Nova

**Cons:**
- Query cần eager load `metas` thay vì truy cập trực tiếp
- Repository cần `getMeta()` / `setMeta()` helpers
- Không thể dùng `$user->avatar` trực tiếp — phải qua Entity

**→ Chọn B (user_meta key-value).** Users table giữ nguyên. Mọi profile metadata đi qua `user_meta`. Repository xử lý mapping — Domain/Application layer không biết cấu trúc DB.

---

## Tổng hợp Decisions

| Câu hỏi | Quyết định | Lý do |
|---|---|---|
| **Lưu file avatar** | Local Storage — `public` disk | Đủ cho v1; đổi sang S3 chỉ cần đổi config driver |
| **Lưu profile data (DB)** | `user_meta` key-value table | Extensible, `users` table chỉ chứa auth data |
| **Controller** | `ProfileController` mới — **không extend UserController** | `UserController` quản lý users (admin). `ProfileController` quản lý chính mình. SRP rõ ràng |
| **UserEntity** | Tạo `Domain/User/Entities/UserEntity.php` | Đúng Clean Architecture — nhất quán với TaskEntity, ProjectEntity |
| **Email verify** | Không yêu cầu re-verify (v1) | Đơn giản; thêm warning UI "kiểm tra email trước khi lưu" |
| **Thông tin phụ của user** | `user_meta` key-value table | avatar path, phone, bio, timezone — tất cả vào `user_meta`, không thêm column vào `users` |

---

## Related Documents

- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — Chi tiết thiết kế
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Build steps
