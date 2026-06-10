# Tenant Settings — Requirements

**Status:** Draft  
**Level:** Product & Developer reading  
**Purpose:** Define what settings are configurable and functional requirements

---

## 🎯 Functional Requirements

### FR1: Settings Storage
- Settings stored in `tenants.settings` JSON column
- One JSON object per tenant containing all settings
- Settings retrieved via `tenantSetting(key, default)` helper

### FR2: Email Notification Settings
- **FR2.1:** Admin can enable/disable email for each event type
  - task.assigned
  - task.status_changed
  - tenant.member_added
  - tenant.member_removed
  - tenant.role_changed
- **FR2.2:** Default: all events enabled
- **FR2.3:** Changes apply immediately (no cache)

### FR3: Notification Retention Settings
- **FR3.1:** Admin can set how many days to keep notifications (7, 14, 30, 60, 90)
- **FR3.2:** Cleanup command reads this setting
- **FR3.3:** Older notifications automatically deleted daily at 3 AM
- **FR3.4:** Default: 30 days

### FR4: Localization Settings
- **FR4.1:** Admin can set timezone for tenant (UTC, Asia/Ho_Chi_Minh, etc.)
- **FR4.2:** Admin can set language (en, vi, etc.)
- **FR4.3:** Admin can set date format (d/m/Y, Y-m-d, etc.)
- **FR4.4:** UI respects timezone for all date/time displays
- **FR4.5:** Default: UTC, English, d/m/Y

### FR5: Member Default Role
- **FR5.1:** Admin can set default role for new members (member, editor, viewer)
- **FR5.2:** When inviting users, they get this role
- **FR5.3:** Default: member

### FR6: Settings UI
- **FR6.1:** Dedicated "Settings" tab in Tenant detail page
- **FR6.2:** Organized by sections (Email, Notifications, Localization, Members)
- **FR6.3:** Form shows current values with defaults
- **FR6.4:** Save button persists changes
- **FR6.5:** Toast notification on success/error

### FR7: Permissions
- **FR7.1:** Only admin/owner can view Settings tab
- **FR7.2:** Only admin/owner can edit settings
- **FR7.3:** Others: cannot see Settings option
- **FR7.4:** Guests: cannot access

### FR8: Settings Usage
- **FR8.1:** NotificationService checks email setting before sending
- **FR8.2:** CleanupCommand reads retention_days setting
- **FR8.3:** Views apply timezone to dates
- **FR8.4:** AttachUserUseCase applies default member role

---

## 🚀 Non-Functional Requirements

### Performance
- **NFR1.1:** Settings lookup ≤ 5ms (1 JSON query)
- **NFR1.2:** Setting not found should fallback to default (no DB error)
- **NFR1.3:** Changes visible immediately (no caching)

### Scalability
- **NFR2.1:** Support unlimited settings (JSON expandable)
- **NFR2.2:** No performance degradation with 1000+ tenants
- **NFR2.3:** Can add new settings without migration

### Reliability
- **NFR3.1:** Invalid setting keys don't crash app (use default)
- **NFR3.2:** Malformed JSON doesn't crash (validation on save)
- **NFR3.3:** Type mismatches rejected on save (e.g., string when expecting boolean)

### Security
- **NFR4.1:** SQL injection protection (use parameterized queries)
- **NFR4.2:** XSS protection (escape HTML in forms)
- **NFR4.3:** Authorization check on every setting read/write
- **NFR4.4:** Cannot modify another tenant's settings

### Testability
- **NFR5.1:** Settings accessible in unit tests (no global state)
- **NFR5.2:** Can mock settings for testing
- **NFR5.3:** NullTenantSettingService for test isolation

---

## 📊 Settings Matrix

| Setting | Type | Default | Phase | Notes |
|---------|------|---------|-------|-------|
| `email.task_assigned` | boolean | true | 1 | Send email when assigned |
| `email.task_status_changed` | boolean | true | 1 | Send email on status change |
| `email.tenant_member_added` | boolean | true | 1 | Send email when member joins |
| `email.tenant_member_removed` | boolean | true | 1 | Send email when member removed |
| `email.tenant_role_changed` | boolean | true | 1 | Send email on role change |
| `notifications.retention_days` | integer | 30 | 1 | Keep notifications N days |
| `localization.timezone` | string | UTC | 1 | Tenant timezone |
| `localization.locale` | string | en | 1 | Language code |
| `localization.date_format` | string | d/m/Y | 1 | Date display format |
| `members.default_role` | string | member | 1 | Role for new members |
| *(reserved for Phase 2)* | | | | |
| `branding.logo_url` | string | null | 2 | Custom tenant logo |
| `branding.primary_color` | string | null | 2 | Custom brand color |
| `gdpr.data_retention_days` | integer | 180 | 2 | GDPR compliance |
| `notifications.digest_enabled` | boolean | false | 2 | Daily digest email |

---

## ✅ Success Criteria

### Functional
- [ ] Admin can view all settings via Settings tab
- [ ] Admin can modify each setting and see changes
- [ ] Settings apply immediately to system behavior
- [ ] Non-admin cannot access Settings tab
- [ ] Invalid values rejected with error message
- [ ] Empty/null values use defaults

### Non-Functional
- [ ] Setting lookup in <5ms
- [ ] No SQL errors for non-existent keys
- [ ] Settings work in unit tests
- [ ] No hardcoded settings remaining
- [ ] Type validation on save (boolean/integer/string)

### UX
- [ ] Settings organized in logical sections
- [ ] Form labels clear and helpful
- [ ] Save feedback (toast notifications)
- [ ] Current values always visible
- [ ] Reset to defaults option available

---

## 🔄 Data Flow Examples

### Example 1: Enable/Disable Email for Task Assignment

```
User: Admin clicks email checkbox → unchecks "Task Assigned"
  ↓
Form: POST /admin/tenant/5/settings/email.task_assigned
  ↓
Controller: UpdateTenantSettingRequest validates
  ↓
UseCase: UpdateTenantSettingUseCase
  ├─ Validates: key exists, value is boolean
  ├─ Updates: tenants.settings JSON
  └─ Returns: success
  ↓
UI: Toast "Settings saved"
  ↓
Later: When task assigned...
  NotificationService reads tenantSetting('email.task_assigned', true, $tenantId)
    → returns false
    → skips email, only in-app notification sent
```

### Example 2: Change Timezone

```
Admin: Selects Asia/Ho_Chi_Minh from dropdown
  ↓
Save: POST /admin/tenant/5/settings/localization.timezone
  ↓
Validate & persist
  ↓
Later: Date displayed in views
  $tz = tenantSetting('localization.timezone', 'UTC');
  $date->setTimezone($tz);
  → All dates now show in Vietnam time
```

---

## 🎬 User Stories

### Story 1: Admin disables email for non-critical events
```
As: Tenant Admin
I want to: Turn off emails for task status changes
So that: Team doesn't get overwhelmed with emails

Acceptance criteria:
- I see Settings tab with Email section
- Task Status Changed has checkbox
- I can uncheck it
- Changes apply immediately
- No more emails for that event
```

### Story 2: Adjust notification cleanup retention
```
As: Tenant Admin
I want to: Keep notifications for 90 days instead of 30
So that: We have longer history for reference

Acceptance criteria:
- Notifications tab has retention slider
- Default is 30 days
- I can change to 90 days
- Save button works
- Cleanup command uses new value
```

### Story 3: Set tenant timezone
```
As: Tenant Admin (Vietnam team)
I want to: Set timezone to Asia/Ho_Chi_Minh
So that: All dates show in correct timezone

Acceptance criteria:
- Localization tab has Timezone dropdown
- Contains common timezones
- I can select Vietnam timezone
- All dates in UI show in that timezone
```

---

## 📋 Edge Cases

### Edge Case 1: What if JSON is malformed?
- **Solution:** Validate JSON on save, show error to user

### Edge Case 2: What if setting key doesn't exist?
- **Solution:** Return default value, don't error

### Edge Case 3: What if admin deletes settings.json?
- **Solution:** Query returns null, code uses default

### Edge Case 4: What if multiple admins change settings simultaneously?
- **Solution:** Last write wins (atomic JSON update)

### Edge Case 5: Upgrade adds new settings, old tenants don't have them?
- **Solution:** Default fallback in tenantSetting() helper

---

## 📖 References

Similar implementations:
- **Slack:** Workspace Settings
- **GitHub:** Organization Settings
- **Stripe:** Account Settings

---

## Next: Architecture

Read [02-architecture.md](./02-architecture.md) to understand class design and data structure.
