# Codebase Context — Laravel Dashboard SAAS

> Quick reference for the entire source structure so AI (or a new developer) can get up to speed in minutes.

---

## 1. Project Goals

A multi-tenant admin dashboard combined with AI-powered English learning features. Two main flows:

1. **Admin Dashboard** — manage Tenants and Projects, session-based authentication.
2. **English Learning** — AI agent for English practice, communicates via REST API + Sanctum token.

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Auth | Laravel Breeze (session) + Custom Auth + Sanctum (API) |
| Reactive UI | Livewire 4 + Alpine.js |
| CSS | Tailwind CSS 3 + SASS |
| Build | Vite 8 |
| Database | SQLite by default (easily switchable to MySQL/PostgreSQL) |
| AI | Laravel AI `^0.6.7` (supports 13+ providers: Anthropic, OpenAI, Gemini…) |
| Permissions | `spatie/laravel-permission` |

---

## 3. Architecture Overview

```
Request
  │
  ├─ routes/web.php          → Controller → Service → Model → DB
  ├─ routes/api.php          → Controller → Service → AI Agent → DB
  ├─ routes/auth.php         → Breeze Auth Controllers
  └─ routes/english.php      → EnglishController → EnglishAgentService
```

### Core Patterns

- **Service-Interface Pattern**: all business logic lives in `Services/Impl/`, exposed through `Services/Contracts/`.
- **DTO Pattern**: input data is wrapped via `app/DTOs/` before being passed into a Service.
- **Form Request Validation**: validation is centralised in `app/Http/Requests/`.
- **Global Scope**: `TenantScope` automatically filters queries by the current tenant (read from `session('current_tenant_id')`).

---

## 4. Directory Structure

```
app/
├── Ai/Agents/
│   └── EnglishEgent.php         # AI agent: English tutor, implements Tools + Conversational
├── DTOs/
│   ├── tenants/CreateTenantDTO.php
│   └── englishs/PromptGenerateMessageDTO.php
├── Http/
│   ├── Controllers/
│   │   ├── TenantController.php      # Resource CRUD for Tenant
│   │   ├── ProjectController.php     # Resource CRUD for Project
│   │   ├── Auth/                     # Breeze auth controllers
│   │   ├── CustomAuth/               # Custom login controller
│   │   └── English/EnglishController.php
│   └── Requests/
│       ├── StoreTenantRequest.php
│       ├── StoreProjectRequest.php
│       ├── UpdateProjectRequest.php
│       └── Auth/LoginRequest.php
├── Models/
│   ├── User.php                 # BelongsToMany Tenant
│   ├── Tenant.php               # HasMany Project; uses TenantScope
│   ├── Project.php              # BelongsTo Tenant
│   └── Scopes/TenantScope.php
├── Providers/
│   └── AppServiceProvider.php   # Binds Interfaces → Implementations
├── Repositories/                # Scaffolded structure (no implementations yet)
│   ├── Contracts/
│   └── Impl/
├── Services/
│   ├── Contracts/
│   │   ├── TenantServiceInterface.php
│   │   ├── ProjectServiceInterface.php
│   │   └── EnglishEgentServiceInterface.php
│   └── Impl/
│       ├── TenantService.php
│       ├── ProjectService.php
│       └── EnglishAgentService.php
├── Traits/                      # Scaffolded structure (no implementations yet)
└── View/Components/
    ├── AppLayout.php
    ├── GuestLayout.php
    ├── SidebarLink.php
    └── modal.php

database/
├── migrations/
│   ├── *_create_users_table.php
│   ├── *_create_tenants_table.php           # slug unique, soft delete, settings json
│   ├── *_create_tenant_user_table.php       # pivot: tenant_id, user_id, role
│   ├── *_create_projects_table.php          # FK tenant_id, owner_id; soft delete
│   ├── *_create_agent_conversations_table.php
│   └── *_create_personal_access_tokens_table.php
└── seeders/DatabaseSeeder.php

resources/
├── js/
│   ├── app.js                   # Entry point (Bootstrap + Alpine)
│   ├── pages/
│   │   ├── dashboard.js
│   │   ├── project.js
│   │   └── tenant.js
│   └── utils/
│       ├── constants.js
│       └── format.js
├── scss/
│   ├── app.scss                 # SASS entry (@use bases/global, @tailwind)
│   └── bases/global.scss
└── views/
    ├── admin/
    │   ├── layouts/app.blade.php
    │   ├── pages/
    │   │   ├── dashboard/index.blade.php
    │   │   ├── tenant/{index,create}.blade.php
    │   │   └── project/{index,create}.blade.php
    │   └── partials/{header,sidebar,footer}.blade.php
    ├── auth/                    # Breeze auth views
    ├── components/
    │   ├── livewire/
    │   │   ├── modal.blade.php
    │   │   └── tenants/
    │   └── [generic Blade components]
    ├── custom-auth/login.blade.php
    ├── english/
    │   ├── index.blade.php
    │   └── layouts/app.blade.php
    └── layouts/
        ├── app.blade.php        # Primary authenticated layout
        └── guest.blade.php

routes/
├── web.php        # /login (guest), /admin/* (auth)
├── api.php        # Sanctum-protected English API endpoints
├── auth.php       # Breeze: register, login, password reset, email verify
├── english.php    # English learning routes
└── console.php
```

---

## 5. Multi-Tenancy

- **Model**: `User` ↔ `Tenant` (many-to-many via `tenant_user` pivot, includes `role`)
- **TenantScope**: global scope on the `Tenant` model, filters by `session('current_tenant_id')`
- **Project isolation**: `projects.tenant_id` is a FK; a Project belongs to exactly one Tenant
- **Tenant Switcher**: Livewire component `tenant-switcher.blade.php`

---

## 6. AI / English Agent

- **Class**: `app/Ai/Agents/EnglishEgent.php` — implements `Agent`, `Conversational`, `HasTools`
- **Service**: `EnglishAgentService` invokes the agent and persists conversations to DB (`agent_conversations`, `agent_conversation_messages`)
- **Capabilities**:
  - Generate English practice prompts
  - Score grammar accuracy
  - Suggest alternative sentences
- **AI Config**: `config/ai.php` — defines multiple providers; switch provider via `.env`

---

## 7. Authentication

| System | File | Used for |
|---|---|---|
| Breeze (session) | `routes/auth.php` + `Auth/` controllers | Web admin |
| Custom Login | `CustomAuth/AuthenticatedSessionController.php` | Custom login page |
| Sanctum (token) | `routes/api.php` | English API |

---

## 8. Service Provider Bindings

`AppServiceProvider.php` binds interfaces → implementations:

```php
TenantServiceInterface       → TenantService
ProjectServiceInterface      → ProjectService
EnglishEgentServiceInterface → EnglishAgentService
```

---

## 9. Database Schema Summary

| Table | Notable columns |
|---|---|
| `users` | id, name, email, password, email_verified_at |
| `tenants` | id, name, slug (unique), is_active, trial_ends_at, settings (json), soft delete |
| `tenant_user` | tenant_id, user_id, role — unique(tenant_id, user_id) |
| `projects` | id, tenant_id, owner_id (→users), name, description, status, soft delete |
| `agent_conversations` | id, user_id, title |
| `agent_conversation_messages` | id, conversation_id, role, content, tool_calls, tool_results, usage |
| `personal_access_tokens` | Sanctum standard |

---

## 10. Frontend

- **Alpine.js**: lightweight interactivity (dropdowns, modal toggles…)
- **Livewire 4**: reactive components without separate API endpoints (modal, tenant-switcher)
- **Tailwind 3**: utility-first; custom `primary` color palette (50–900) + `dark` theme
- **SASS**: mainly for global variables/mixins (`resources/scss/bases/global.scss`)
- **Vite**: entry points `resources/scss/app.scss` + `resources/js/app.js`, CORS `http://127.0.0.2:8002`

---

## 11. Key Working Notes

1. **Adding a new feature** → create an Interface in `Services/Contracts/`, an Impl in `Services/Impl/`, and register the binding in `AppServiceProvider`.
2. **Adding an admin route** → place it inside the `auth` middleware group in `routes/web.php` under the `/admin` prefix.
3. **Tenant-scoped queries** → verify whether `TenantScope` is active; use `withoutGlobalScope` when you need unscoped results.
4. **Switching AI provider** → change via `.env`; no agent code changes required.
5. **Livewire components** → views go in `resources/views/components/livewire/`, classes in `app/Livewire/` (if present).
6. **Soft deletes** → both `Tenant` and `Project` use soft delete; call `withTrashed()` to include deleted records.
