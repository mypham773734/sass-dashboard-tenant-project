# Audit System — Requirements

---
Version: 1.0
Last Updated: 2026-06-06
Status: Draft
Author: Product Team
---

## Functional Requirements

### FR1: Audit Event Types

Hệ thống phải ghi nhận các sự kiện theo **5 nhóm**:

#### Auth Events
| Event Key | Trigger | Data cần ghi |
|---|---|---|
| `auth.login` | User login thành công | user_id, ip, user_agent |
| `auth.logout` | User logout | user_id |
| `auth.login_failed` | Sai password | email attempted, ip |

#### Task Events
| Event Key | Trigger | Data cần ghi |
|---|---|---|
| `task.created` | Tạo task mới | task_id, title, project_id, assignee_id |
| `task.updated` | Cập nhật task | task_id, old_values, new_values |
| `task.deleted` | Xoá task | task_id, title (snapshot) |
| `task.status_changed` | Đổi status | task_id, old_status, new_status |
| `task.assigned` | Assign task cho user | task_id, assigned_to |

#### Project Events
| Event Key | Trigger | Data cần ghi |
|---|---|---|
| `project.created` | Tạo project mới | project_id, name |
| `project.updated` | Cập nhật project | project_id, old_values, new_values |
| `project.deleted` | Xoá project | project_id, name (snapshot) |

#### Tenant Events
| Event Key | Trigger | Data cần ghi |
|---|---|---|
| `tenant.updated` | Cập nhật thông tin tenant | tenant_id, old_values, new_values |
| `tenant.user_invited` | Mời user vào tenant | invited_email, role |
| `tenant.user_removed` | Xoá user khỏi tenant | removed_user_id |
| `tenant.user_role_changed` | Đổi role của user | user_id, old_role, new_role |

#### Permission Events
| Event Key | Trigger | Data cần ghi |
|---|---|---|
| `permission.role_assigned` | Gán role cho user | user_id, role_name, tenant_id |
| `permission.role_revoked` | Thu hồi role của user | user_id, role_name, tenant_id |

---

### FR2: Audit Log Data Structure

Mỗi audit log record phải chứa:

```
audit_logs:
  id            — Primary key (auto increment)
  tenant_id     — Nullable (null = system-level event như auth.login_failed)
  user_id       — Nullable (null = system action)
  action        — String (VD: "task.created", "auth.login")
  entity_type   — String (VD: "Task", "Project", "Tenant")
  entity_id     — Nullable Int (ID của resource bị tác động)
  old_values    — JSON (state trước khi thay đổi — null cho creates)
  new_values    — JSON (state sau khi thay đổi — null cho deletes)
  ip_address    — String
  user_agent    — String (nullable)
  metadata      — JSON (context thêm — VD: route name, request method)
  created_at    — Timestamp (immutable — không có updated_at)
```

**Rules:**
- Không có `updated_at` — audit log không bao giờ bị edit
- `old_values` / `new_values` chỉ ghi các fields **quan trọng** (không dump toàn bộ model)
- Sensitive fields (password, token) **không bao giờ** được ghi vào audit log

---

### FR3: Audit Log Viewer (UI)

Owner và Admin có thể xem audit logs của tenant họ qua UI:

**Phải có:**
- Timeline view — theo thứ tự giảm dần (mới nhất lên đầu)
- Filter theo: `user`, `action type`, `date range`, `entity type`
- Pagination (20 items/page)
- Human-readable labels (VD: "John created task 'Fix bug #123'")
- Expand row để xem `old_values` / `new_values` chi tiết

**Không cần (v1):**
- Export CSV/PDF
- Real-time live stream
- Search full-text trong values

---

### FR4: Immutability

- Audit logs **KHÔNG BAO GIỜ** bị UPDATE hay DELETE qua application code
- Không có Delete endpoint cho audit logs
- Database-level: không cấp quyền DELETE trên bảng `audit_logs` cho application user (migration note)
- Retention policy: xoá records cũ hơn `AUDIT_RETENTION_DAYS` days **chỉ qua scheduled job** (không phải user action)

---

### FR5: Multi-Tenant Isolation

- Owner/Admin của tenant A **không thể** xem audit logs của tenant B
- Mọi query audit logs phải có `WHERE tenant_id = ?`
- Auth events (login/logout) có `tenant_id = null` — không hiển thị trong tenant viewer
  - Nhưng có thể join với `tenant_user` để show "user John logged in" trong context của tenant đó

---

### FR6: Performance

- Ghi audit log **không được** làm chậm request chính quá **5ms**
- Dùng **queued jobs** (asynchronous) cho việc ghi — fire-and-forget
- Database indexes đầy đủ để query viewer < 100ms với 1M records

---

## Non-Functional Requirements

### Performance
- Write: < 5ms impact trên request chính (async queue)
- Read: Audit log viewer < 100ms (với index đúng)
- Storage: Estimate ~1KB/event → 1M events = 1GB → acceptable

### Security
- Audit logs chỉ được xem bởi Owner và Admin của tenant
- Không expose `old_values`/`new_values` chứa sensitive data
- IP address của user phải được mask nếu GDPR yêu cầu (config flag)

### Scalability
- Partition bảng `audit_logs` theo `created_at` month (migration note cho tương lai)
- Index trên `(tenant_id, created_at)` và `(tenant_id, user_id, created_at)`

### Maintainability
- Thêm event mới chỉ cần: tạo Event class + register Listener — không sửa core
- Event keys follow convention: `{entity}.{action}` (lowercase, snake_case)
- AuditLogger là service được inject — dễ mock trong tests

---

## Constraints & Assumptions

### Constraints
- Không dùng package ngoài (owen-it/laravel-auditing) — tự build để kiểm soát hoàn toàn
- Phải fit vào Clean Architecture (Domain/Application/Infrastructure)
- Multi-tenant: `tenant_id` luôn tường minh trong mọi query
- Queue driver phải được setup (database queue là acceptable cho v1)

### Assumptions
- User đã authenticated khi perform mọi action (trừ `auth.login_failed`)
- Mỗi Use Case dispatch đúng 1 event — không batch
- Clock là server time (UTC) — không có client-side timestamp
- `old_values` cho Updates lấy từ Entity trước khi execute use case

---

## Action Taxonomy

```
auth.*          — Authentication events (không có tenant scope)
task.*          — Task CRUD + status + assign
project.*       — Project CRUD
tenant.*        — Tenant settings + member management
permission.*    — Role assignment / revocation
```

**Naming convention:** `{entity}.{past_tense_verb}`

VD: `task.created` ✓ | `task.create` ✗ | `createTask` ✗

---

## Related Documents

- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) — System design và diagrams
- [03-APPROACHES.md](./03-APPROACHES.md) — So sánh các implementation approach
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Step-by-step plan
