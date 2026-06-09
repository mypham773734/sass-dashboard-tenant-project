# User Profile — Overview

---
Version: 1.0
Last Updated: 2026-06-06
Status: Draft
Author: Architecture Team
---

## Problem Statement

Hệ thống hiện tại **không có trang Profile** cho user đang login:

- Không thể đổi tên hiển thị
- Không thể đổi email
- Không thể đổi password sau khi đăng ký
- Không có avatar — sidebar đang hardcode "JD"
- Không biết mình đang thuộc tenant nào với role gì

---

## Solution Summary

Xây dựng **trang Profile** cho phép user tự quản lý thông tin cá nhân:

- **Thông tin cơ bản**: name, email, avatar
- **Bảo mật**: đổi password (yêu cầu nhập password hiện tại)
- **Tenant overview**: xem danh sách tenants mình đang tham gia + role tương ứng (read-only)

**Key principle:** Profile là của chính user — không liên quan đến tenant scope. `ProfileController` tách biệt hoàn toàn với `UserController` (dành cho admin quản lý user khác).

---

## Scope

### ✅ In Scope

- Update name
- Update email (không cần re-verify cho v1)
- Upload avatar (lưu local storage)
- Change password (yêu cầu current password)
- Xem danh sách tenants + role (read-only)
- Audit log: ghi lại khi profile được update, khi password đổi

### ❌ Out of Scope

- Email verification sau khi đổi email (v2)
- Two-factor authentication (v2)
- Social login (Google, GitHub) link/unlink (v2)
- Timezone / locale preferences (v2)
- Notification preferences (v2)
- Delete account (v2 — cần soft delete flow phức tạp)

---

## Timeline

| Phase | Duration | Deliverable |
|---|---|---|
| **Phase 1: DB + Domain** | 0.5 ngày | Migration thêm `avatar`, `phone`, Entity, Repository |
| **Phase 2: Use Cases** | 0.5 ngày | `GetProfileUseCase`, `UpdateProfileUseCase`, `ChangePasswordUseCase` |
| **Phase 3: HTTP** | 0.5 ngày | `ProfileController`, Form Requests, Routes |
| **Phase 4: UI** | 1 ngày | Blade view — 3 sections: info, password, tenants |
| **Phase 5: Testing** | 0.5 ngày | Feature tests cho update + change password |
| **Total** | **3 ngày** | Production-ready Profile page |

---

## Success Criteria

- [ ] User có thể cập nhật name và email
- [ ] User có thể upload avatar — hiển thị trên sidebar
- [ ] User có thể đổi password khi biết password hiện tại
- [ ] Sai current password → validation error rõ ràng
- [ ] User thấy danh sách tenants mình thuộc về + role
- [ ] Mọi thay đổi profile đều được ghi vào audit log
- [ ] Không thể sửa profile của người khác (chỉ `auth()->user()`)
- [ ] Tests cover happy path + validation failures

---

## Related Documents

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) — Functional requirements chi tiết
- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — Data model, flow diagrams
- [03-APPROACHES.md](./03-APPROACHES.md) — Các lựa chọn thiết kế, lý do chọn
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Step-by-step build plan
