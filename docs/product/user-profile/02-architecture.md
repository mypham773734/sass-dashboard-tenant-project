# User Profile — Architecture

---
Version: 1.0
Last Updated: 2026-06-06
Status: Draft
Author: Architecture Team
---

## System Overview

```mermaid
graph TB
    subgraph Presentation["🖥️ Presentation Layer"]
        ProfileController["ProfileController\nshow() / update() / changePassword()"]
        UpdateProfileRequest["UpdateProfileRequest\n(validation)"]
        ChangePasswordRequest["ChangePasswordRequest\n(validation)"]
    end

    subgraph Application["📋 Application Layer"]
        GetProfileUseCase["GetProfileUseCase\n+ execute(userId)"]
        UpdateProfileUseCase["UpdateProfileUseCase\n+ execute(dto, userId)"]
        ChangePasswordUseCase["ChangePasswordUseCase\n+ execute(dto, userId)"]
        AuditLogger["AuditLoggerInterface\n(inject)"]
    end

    subgraph Domain["🔧 Domain Layer"]
        UserEntity["UserEntity\n(id, name, email, phone,\navatar, tenants)"]
        UserRepoInterface["UserRepositoryInterface\n+ findById(id)\n+ update(UserEntity)\n+ updatePassword(id, hash)"]
    end

    subgraph Infrastructure["⚙️ Infrastructure Layer"]
        EloquentUserRepo["EloquentUserRepository"]
        AvatarStorage["AvatarStorage\n(Storage::disk public)"]
        DB[("users table")]
        MetaDB[("user_meta table")]
    end

    ProfileController -->|invoke| GetProfileUseCase
    ProfileController -->|invoke| UpdateProfileUseCase
    ProfileController -->|invoke| ChangePasswordUseCase
    UpdateProfileUseCase -->|uses| UserRepoInterface
    UpdateProfileUseCase -->|log| AuditLogger
    ChangePasswordUseCase -->|uses| UserRepoInterface
    ChangePasswordUseCase -->|log| AuditLogger
    UserRepoInterface -->|implemented by| EloquentUserRepo
    UpdateProfileUseCase -->|upload via| AvatarStorage
    EloquentUserRepo --> DB
    EloquentUserRepo --> MetaDB
```

---

## Database Changes

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email "unique"
        timestamp email_verified_at "nullable"
        string password "hashed"
        string remember_token "nullable"
        timestamp created_at
        timestamp updated_at
    }

    USER_META {
        bigint id PK
        bigint user_id FK
        string key "avatar, phone, bio, timezone..."
        text value "nullable"
        timestamp created_at
        timestamp updated_at
    }

    TENANTS ||--o{ TENANT_USER : "has members"
    USERS   ||--o{ TENANT_USER : "belongs to"
    USERS   ||--o{ USER_META   : "has meta"

    TENANT_USER {
        int tenant_id FK
        int user_id FK
        timestamp created_at
    }
```

**Migration cần tạo:** tạo bảng `user_meta` mới — bảng `users` không thay đổi.

**Unique constraint:** `(user_id, key)` — mỗi user chỉ có 1 record cho mỗi key.

---

## Clean Architecture Layer Mapping

```mermaid
classDiagram
    class UserEntity {
        +int id
        +string name
        +string email
        +string|null phone
        +string|null avatar
        +string|null avatarUrl
        +array tenants
    }

    class UserRepositoryInterface {
        <<interface — Domain>>
        +findById(int id) UserEntity
        +update(UserEntity entity) UserEntity
        +updatePassword(int id, string hashedPassword) void
    }

    class UpdateProfileDTO {
        <<Application>>
        +string name
        +string email
        +string|null phone
        +string|null avatarPath
        +fromArray(array) self
    }

    class ChangePasswordDTO {
        <<Application>>
        +string currentPassword
        +string newPassword
        +fromArray(array) self
    }

    class GetProfileUseCase {
        +execute(int userId) UserEntity
    }

    class UpdateProfileUseCase {
        -UserRepositoryInterface repo
        -AuditLoggerInterface audit
        +execute(UpdateProfileDTO dto, int userId) UserEntity
    }

    class ChangePasswordUseCase {
        -UserRepositoryInterface repo
        -AuditLoggerInterface audit
        +execute(ChangePasswordDTO dto, int userId) void
    }

    class EloquentUserRepository {
        <<Infrastructure>>
        +findById(int id) UserEntity
        +update(UserEntity entity) UserEntity
        +updatePassword(int id, string hash) void
        -toEntity(Model model, Collection metas) UserEntity
        -getMeta(int userId, string key) string|null
        -setMeta(int userId, string key, string|null value) void
    }

    UserRepositoryInterface <|.. EloquentUserRepository
    UpdateProfileUseCase --> UserRepositoryInterface
    ChangePasswordUseCase --> UserRepositoryInterface
    GetProfileUseCase --> UserRepositoryInterface
```

---

## Flow — Update Profile

```mermaid
sequenceDiagram
    participant Browser
    participant ProfileController
    participant UpdateProfileUseCase
    participant AvatarStorage
    participant EloquentUserRepo
    participant AuditLogger
    participant DB

    Browser->>ProfileController: POST /profile (name, email, phone, avatar?)
    ProfileController->>ProfileController: UpdateProfileRequest::validate()

    alt Has avatar file
        ProfileController->>AvatarStorage: store(file, 'avatars')
        AvatarStorage-->>ProfileController: avatarPath
        ProfileController->>AvatarStorage: delete(old avatar path)
    end

    ProfileController->>UpdateProfileUseCase: execute(dto, auth()->id())
    UpdateProfileUseCase->>EloquentUserRepo: findById(userId)
    EloquentUserRepo-->>UpdateProfileUseCase: UserEntity (oldValues)
    UpdateProfileUseCase->>EloquentUserRepo: update(newUserEntity)
    EloquentUserRepo->>DB: UPDATE users SET name=?, email=?
    EloquentUserRepo->>MetaDB: UPSERT user_meta (avatar, phone)
    UpdateProfileUseCase->>AuditLogger: log('profile.updated', oldValues, newValues)
    UpdateProfileUseCase-->>ProfileController: UserEntity
    ProfileController-->>Browser: redirect /profile with success
```

---

## Flow — Change Password

```mermaid
sequenceDiagram
    participant Browser
    participant ProfileController
    participant ChangePasswordUseCase
    participant EloquentUserRepo
    participant AuditLogger
    participant DB

    Browser->>ProfileController: POST /profile/password (current, new, confirm)
    ProfileController->>ProfileController: ChangePasswordRequest::validate()

    ProfileController->>ChangePasswordUseCase: execute(dto, auth()->id())
    ChangePasswordUseCase->>EloquentUserRepo: findById(userId)
    EloquentUserRepo-->>ChangePasswordUseCase: UserEntity

    alt current_password incorrect
        ChangePasswordUseCase-->>ProfileController: throw DomainException
        ProfileController-->>Browser: back with error "Current password is incorrect"
    else correct
        ChangePasswordUseCase->>EloquentUserRepo: updatePassword(userId, bcrypt(newPassword))
        EloquentUserRepo->>DB: UPDATE users SET password = ?, remember_token = null
        ChangePasswordUseCase->>AuditLogger: log('profile.password_changed')
        ChangePasswordUseCase-->>ProfileController: void
        ProfileController-->>Browser: redirect /profile with success
    end
```

**Lưu ý:** `remember_token = null` để invalidate tất cả "Remember me" sessions khác.

---

## UI Layout

```mermaid
graph TD
    A["GET /profile — ProfileController@show"] --> B["profile/index.blade.php"]

    B --> C["Section 1: Avatar + Basic Info\n- Avatar upload preview\n- name, email, phone fields\n- Save button"]

    B --> D["Section 2: Change Password\n- current_password\n- new_password\n- new_password_confirmation\n- Update Password button"]

    B --> E["Section 3: Tenant Memberships (read-only)\n- Table: Tenant name · Role · Status\n- Switch Tenant button per row"]

    C -->|POST /profile| F["ProfileController@update"]
    D -->|POST /profile/password| G["ProfileController@changePassword"]
```

---

## Avatar Storage Strategy

```mermaid
graph LR
    A["User uploads file\nmax 2MB\nJPEG/PNG/WEBP"] --> B["ProfileController\nvalidate MIME + size"]
    B --> C["Storage::disk('public')\n.putFileAs('avatars', file, userId.ext)"]
    C --> D["storage/app/public/avatars/1.jpg"]
    D -->|symlink| E["public/storage/avatars/1.jpg"]
    E -->|URL| F["asset('storage/avatars/1.jpg')"]

    G["Old avatar exists?"] -->|Yes| H["Storage::delete(old_path)"]
    H --> C
    G -->|No| C
```

**Avatar path được lưu vào `user_meta` với key `avatar`:**
```
user_meta: { user_id: 1, key: 'avatar', value: 'avatars/1.jpg' }
```

**Avatar URL helper trên UserEntity:**
```
avatarUrl = avatar != null
    ? asset('storage/' + avatar)
    : null  (caller dùng initials fallback)
```

`EloquentUserRepository::update()` gọi `setMeta($userId, 'avatar', $path)` sau khi lưu file.

---

## Sidebar Integration

```mermaid
graph LR
    A["sidebar.blade.php\n(currently hardcoded 'JD')"] -->|replace with| B["auth()->user()->name\ninitials từ name"]
    B --> C{"user->avatar != null?"}
    C -->|Yes| D["<img src=avatarUrl> rounded-full"]
    C -->|No| E["Initials div\n(2 chữ cái đầu của name)"]
```

---

## Route Structure

```
GET  /admin/profile              ProfileController@show          profile.show
POST /admin/profile              ProfileController@update        profile.update
POST /admin/profile/password     ProfileController@changePassword profile.password
```

**Không dùng `resource` route** vì Profile không có index/create/destroy — chỉ 3 endpoints.

---

## File Structure

```
app/
├── Domain/
│   └── User/
│       ├── Entities/
│       │   └── UserEntity.php
│       └── Repositories/
│           └── UserRepositoryInterface.php
│
├── Application/
│   └── User/
│       ├── DTOs/
│       │   ├── UpdateProfileDTO.php
│       │   └── ChangePasswordDTO.php
│       └── UseCases/
│           ├── GetProfileUseCase.php
│           ├── UpdateProfileUseCase.php
│           └── ChangePasswordUseCase.php
│
├── Infrastructure/
│   └── Persistence/Repositories/
│       └── EloquentUserRepository.php
│
└── Http/
    ├── Controllers/Admin/
    │   └── ProfileController.php          ← Tách riêng với UserController
    └── Requests/
        ├── UpdateProfileRequest.php
        └── ChangePasswordRequest.php

database/
└── migrations/
    └── 2026_06_06_100000_create_user_meta_table.php

app/Models/
└── UserMeta.php                               ← Eloquent model cho user_meta

resources/views/admin/pages/profile/
└── index.blade.php                        ← 3 sections layout

routes/web.php
└── 3 routes profile.*
```

---

## Security Checklist

```
✅ auth()->user() — không nhận userId từ request
✅ Avatar MIME validation server-side (không tin file extension)
✅ Avatar path sanitized — dùng userId làm filename
✅ Password không bao giờ được log trong audit
✅ remember_token = null khi đổi password (invalidate other sessions)
✅ current_password check trước khi cho đổi
✅ new_password min 8 ký tự
```

---

## Related Documents

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) — Functional requirements
- [03-APPROACHES.md](./03-APPROACHES.md) — Các lựa chọn thiết kế
- [04-IMPLEMENTATION_PLAN.md](./04-IMPLEMENTATION_PLAN.md) — Build steps
