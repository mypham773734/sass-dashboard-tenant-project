# Tenant Settings — Entry Point

**Feature:** Configurable tenant-level settings (no hardcoding)  
**Status:** Planning — Ready to implement  
**Last Updated:** 2026-06-10

---

## Problem

Currently, tenant settings are hardcoded:
- Email notifications: hardcoded in config or env
- Notification retention: hardcoded (always 30 days)
- Timezone/locale: not configurable at all
- No UI for admins to change settings
- Settings changes require code deploy

**Issues:**
- ❌ Not flexible — changing settings requires code change + deploy
- ❌ No audit trail — who changed what when?
- ❌ Per-tenant variation impossible — all tenants have same settings
- ❌ User expectation gap — users expect a Settings UI like Slack/GitHub

---

## Solution

**Tenant Settings** — Config-driven, database-backed, admin UI for per-tenant configuration.

```
Admin clicks Settings tab
    ↓
Form shows current settings (with defaults)
    ↓
Admin changes setting + clicks Save
    ↓
Value saved to tenant_settings table (one row per tenant_id + dot-notation key)
    ↓
Code reads via tenantSetting() helper:
    $emailEnabled = tenantSetting('email.task_assigned', true)
    ↓
    Services/Commands use the value
```

**Core principle:**
- **Centralised:** All settings in a dedicated table (no schema pollution)
- **Typed:** Each setting has expected type (boolean, integer, string)
- **Defaulted:** Fallback to sensible defaults if not set
- **Scoped:** Per-tenant (one admin cannot affect another tenant)
- **Admin-only:** Only admin/owner can change
- **No audit table needed:** Just save current value (no history)

---

## Design Decision: JSON Column vs Separate Table

Three options were evaluated:

| | JSON Column | Separate Table ✅ | Redis/Cache |
|---|---|---|---|
| Query Performance | 1 query but read-modify-write | Per-key upserts | Fast but fragile |
| Extensibility | Easy (add keys) | Easy (add rows) | Not persistent |
| Default handling | Simple (null coalesce) | Simple (null coalesce) | Extra logic |
| Complexity | Low | Low | High |
| Data consistency | Race conditions on JSON blob | Atomic per-key | Stale possible |

**Why Separate Table:** Per-key atomic updates (no read-modify-write races on a shared JSON blob), simple `updateOrCreate` upserts, straightforward caching via `Cache::tags(["tenant:{id}:settings"])` per tenant.

---

## Reading Order

1. **[01-requirements.md](./01-requirements.md)** — Settings to configure, functional & non-functional requirements
2. **[02-architecture.md](./02-architecture.md)** — Layer mapping, entity design, service API
3. **[03-implementation-plan.md](./03-implementation-plan.md)** — 5 phases, file checklist, code examples

---

## Quick Reference

### Store & Read Settings

```php
// Read single setting (with default fallback)
$emailEnabled = tenantSetting('email.task_assigned', true, $tenantId);

// Set a section (via UseCase — updates all keys in that section)
$dto = UpdateTenantSettingDTO::fromArray('email', [
    'email' => [
        'task_assigned' => false,
        'task_status_changed' => true,
        // ...other email settings
    ],
]);
$this->updateSettingUseCase->execute($tenantId, $dto);

// Get all settings (merged with defaults)
$allSettings = app(GetTenantSettingsUseCase::class)->execute($tenantId);
```

### Settings Structure

Logically, `tenantSetting()` and `GetTenantSettingsUseCase` return a nested array:

```json
{
  "email": {
    "task_assigned": true,
    "task_status_changed": true,
    "tenant_member_added": true,
    "tenant_member_removed": true,
    "tenant_role_changed": true
  },
  "notifications": {
    "retention_days": 30
  },
  "localization": {
    "timezone": "UTC",
    "locale": "en"
  },
  "members": {
    "default_role": "member"
  }
}
```

Physically, each leaf setting is one row in `tenant_settings`:

```
| id | tenant_id | key                        | value | created_at | updated_at |
|----|-----------|----------------------------|-------|------------|------------|
| 1  | 1         | email.task_assigned        | true  | ...        | ...        |
| 2  | 1         | email.task_status_changed  | true  | ...        | ...        |
| 3  | 1         | notifications.retention_days | 30  | ...        | ...        |
```

### Use in Services

```php
// Check email before sending
if (tenantSetting('email.task_assigned', true, $tenantId)) {
    sendEmail(...);
}

// Use retention policy
$days = tenantSetting('notifications.retention_days', 30, $tenantId);
deleteOlderThan($before);

// Apply timezone
$tz = tenantSetting('localization.timezone', 'UTC');
$date->setTimezone($tz);
```

---

## UI: Settings Tab + Submenu

Settings is a top-level tab on the Tenant detail page. Inside it, each setting
group (Email, Notifications, Localization, Members) is its own **submenu item
with its own URL** — not a JS tab/anchor. This keeps each section bookmarkable,
keeps forms small/focused, and highlights the active section in the sidebar.

```
/admin/tenant/{id}
├── Overview (basic info)
├── Members (manage users)
├── Projects (view projects)
├── Audit (view logs)
└── Settings ← NEW (has its own submenu)
```

### Settings Submenu (URLs)

```
/admin/tenant/{id}/settings/email           ← default (redirect from /settings)
/admin/tenant/{id}/settings/notifications
/admin/tenant/{id}/settings/localization
/admin/tenant/{id}/settings/members
```

### Layout

```
┌──────────────────────────────────────────────────────────┐
│ Tenant: Acme Corp > Settings                              │
├───────────────┬────────────────────────────────────────────┤
│ ⚙️ Settings    │  📧 Email Notifications                   │
│  📧 Email      │  ┌──────────────────────────────────────┐ │
│  🔔 Notif.     │  │ [x] Task Assigned                    │ │
│  🌍 Localiz.   │  │ [x] Status Changed                   │ │
│  👥 Members    │  │ [x] Member Added / Removed           │ │
│   (active: ▶)  │  │ [x] Role Changed                     │ │
│                │  │                          [Save]       │ │
│                │  └──────────────────────────────────────┘ │
└───────────────┴────────────────────────────────────────────┘
```

Each submenu link is highlighted (active state) based on the current `section`
route segment. Switching sections is a normal page navigation (full page load
or Livewire/Alpine partial swap — implementation detail in
[02-architecture.md](./02-architecture.md)).

---

## Permissions

**Who can access Settings tab?**
- ✅ Tenant Owner
- ✅ Tenant Admins
- ❌ Members
- ❌ Guests

---

## Config

No env variables needed — defaults are sensible:
- Email: all enabled
- Retention: 30 days
- Timezone: UTC
- Locale: English
- Default role: Member

---

## Implementation Timeline

| Phase | Focus | Files | Time |
|---|---|---|---|
| 1 | DB + Domain | 4 | 1h |
| 2 | Service + App | 5 | 1h |
| 3 | UI + Forms | 8 | 1.5h |
| 4 | Integration | 6 | 1h |
| 5 | Tests | 3 | 1h |
| **Total** | | ~26 | **~5h** |

---

## Related

- **Notification System** — Reads `email.*` settings before sending notifications
- **Cleanup Command** — Reads `notifications.retention_days` for auto-cleanup
- **User Experience** — Timezone affects all date/time displays in views
- **Permission System** — Member role from settings applied to new members

---

## Next Steps

1. Read [01-requirements.md](./01-requirements.md) for what needs to be configurable
2. Review [02-architecture.md](./02-architecture.md) for class design
3. Follow [03-implementation-plan.md](./03-implementation-plan.md) to code Phase 1–5
