# User Profile — Documentation Index

**Status:** Draft | **Est:** 3 ngày | **Approach:** ProfileController riêng + Local Storage avatar

## Documents

| File | Mô tả | Status |
|---|---|---|
| [00-OVERVIEW.md](./00-OVERVIEW.md) | Problem, scope, timeline, success criteria | ✅ Done |
| [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) | FR1-6: update info, avatar, change password, tenant view | ✅ Done |
| [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) | Diagrams: flow, DB schema, class hierarchy, UI layout | ✅ Done |
| [03-APPROACHES.md](./03-APPROACHES.md) | 4 design decisions: avatar storage, controller, entity, email verify | ✅ Done |
| [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) | 6 phases, code patterns, checklist 24 items | ✅ Done |

## Quick Summary

**What:** Trang `/profile` cho user tự cập nhật thông tin cá nhân.

**3 sections:**
1. **Basic Info** — name, email, phone, avatar upload
2. **Change Password** — yêu cầu current password, invalidate other sessions
3. **Tenant Memberships** — xem tenants + role (read-only), switch tenant

**New files cần tạo:**
- 1 migration (thêm `avatar`, `phone` vào `users`)
- `Domain/User/` — UserEntity, UserRepositoryInterface
- `Application/User/` — 2 DTOs, 3 Use Cases
- `Infrastructure/` — EloquentUserRepository
- `Http/` — ProfileController, 2 Requests
- 3 routes + 1 Blade view

**Key design decisions:**
- `ProfileController` tách riêng với `UserController` (admin)
- Avatar lưu Local Storage (`public` disk) — đổi sang S3 dễ sau
- Email đổi không cần re-verify (v1)
- `remember_token = null` khi đổi password → invalidate other sessions
