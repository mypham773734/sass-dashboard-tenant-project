# Notification System

**What:** In-app notifications (bell icon + dropdown on dashboard)  
**Status:** Planning  
**For:** New developers — start here!

---

## 🎯 What Does It Do?

When something happens (task assigned, user added, etc.), the system:

1. **Saves notification to DB** — `notifications` table
2. **Shows bell icon with badge** — unread count on header
3. **Displays dropdown** — click bell → see latest 10 notifications
4. **User marks as read** — click notification → goes to resource

### Visual Example

```
Header: [🔔 3]  ← badge shows 3 unread

Click 🔔 → Dropdown:
┌────────────────────────────────┐
│ Notifications    [Mark All ✓]  │
├────────────────────────────────┤
│ ● Alice assigned you "Fix bug" │ ← unread (dot)
│ ○ Bob joined Acme Corp         │ ← read (no dot)
│ ● Role changed: Admin          │
├────────────────────────────────┤
│           [View All]            │
└────────────────────────────────┘
```

---

## 💡 How Does Code Use It?

### Simple: Notify One User

```php
// In UpdateTaskUseCase
$this->notificationService->notifyOne(
    event:    'task.status_changed',
    tenantId: $tenantId,
    userId:   $assigneeId,
    context:  [
        'task_id'    => $task->id,
        'task_title' => $task->title,
        'old_status' => 'todo',
        'new_status' => 'done',
    ]
);
```

### Advanced: Notify Multiple Users

```php
// In DetachUserFromTenantUseCase
$this->notificationService->notify(
    event:       'tenant.member_removed',
    tenantId:    $tenantId,
    recipientIds: [$admin1, $admin2, $removedUserId],  // multiple
    context:     ['removed_user_name' => 'Bob']
);
```

---

## 🏗️ Quick Architecture (Mental Model)

### Event → Notification Flow

```
1. Code triggers event
   ↓
   $this->notificationService->notify('task.assigned', ...)

2. Dispatch to queue (non-blocking)
   ↓
   WriteNotificationJob::dispatch(...)

3. Queue worker processes
   ↓
   Resolve handler (config-driven)
   Render title from template or handler
   Save to notifications table

4. Frontend polls for updates
   ↓
   Livewire component checks every 5s
   Badge updates, dropdown refreshes

5. User sees notification
   ↓
   Bell icon with badge + dropdown
```

### Two Ways to Implement: GenericHandler (Simple) vs BaseHandler (Complex)

| Scenario | Handler | Where Defined |
|---|---|---|
| **Simple:** "Alice assigned you task" | GenericHandler | `config/notification.php` only |
| **Complex:** Query DB for recipients | BaseHandler | Create `.php` class |

**Example:**
- `task.assigned` → GenericHandler (just config)
- `tenant.member_removed` → BaseHandler (needs DB query)

---

## 📚 Reading Path for Developers

### Path 1: "I just need to trigger notifications"
1. See examples below
2. Call `$notificationService->notify()` in UseCase
3. Done!

### Path 2: "I need to understand the system"
1. **[01-requirements.md](./01-requirements.md)** — What events exist, what's a notification
2. **[02-architecture.md](./02-architecture.md)** — How it works (classes, flows)
3. **[03-implementation-plan.md](./03-implementation-plan.md)** — Implementation steps

### Path 3: "Why not use Laravel Notification?" (optional)
See [02-architecture.md § Design Decisions](./02-architecture.md#1-design-decisions)

---

## 🚀 Example: Add New Notification (5 minutes)

### Step 1: Trigger from UseCase

```php
class ApproveInvoiceUseCase {
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function execute(ApproveInvoiceDTO $dto, int $tenantId): void
    {
        // ... business logic ...

        // Notify the requester
        $this->notificationService->notifyOne(
            event:    'invoice.approved',
            tenantId: $tenantId,
            userId:   $invoice->requester_id,
            context:  [
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->number,
            ]
        );
    }
}
```

### Step 2: Add to Config

```php
// config/notification.php
'invoice.approved' => [
    'enabled'           => env('NOTIFICATION_INVOICE_APPROVED', true),
    'handler'           => GenericNotificationHandler::class,
    'recipients'        => 'requester_id',  // from context
    'title_template'    => 'Invoice #{invoice_number} was approved ✓',
    'url_template'      => 'invoice.show:{invoice_id}',
],
```

### Done! ✅

That's it. GenericHandler automatically:
- Renders title: `Invoice #123 was approved ✓`
- Creates URL: `route('invoice.show', 123)`
- Saves to DB
- Shows in bell dropdown

---

## 🎯 Quick Reference: Event Types (MVP)

| Event | Trigger | Recipients | Type |
|---|---|---|---|
| `task.assigned` | Task assigned to user | Assignee | Simple |
| `task.status_changed` | Task status changed | Creator + Assignee | Simple |
| `tenant.member_added` | New user joins | Admins (from DB) | Complex |
| `tenant.member_removed` | User removed | Admins + removed user | Complex |
| `tenant.role_changed` | User role changes | Target user | Complex |

---

## ⚙️ Configuration Checklist

```php
// .env
NOTIFICATION_ENABLED=true                    # Enable/disable all
NOTIFICATION_QUEUE=notifications             # Queue name
NOTIFICATION_TASK_ASSIGNED=true              # Per-event toggle
NOTIFICATION_TENANT_MEMBER_ADDED=true
# ... etc
```

---

## ✅ Implementation Checklist

- [ ] Phase 1: Database table + Entity + Repository
- [ ] Phase 2: Service interface + NullService + Job
- [ ] Phase 3: GenericHandler + 3 BaseHandlers + config
- [ ] Phase 4: Inject into UseCase
- [ ] Phase 5: Livewire bell component
- [ ] Phase 6: Cleanup command
- [ ] Phase 7: Tests

**Estimated:** 6-7 sessions (see [03-implementation-plan.md](./03-implementation-plan.md) for details)

---

## ❓ Common Questions

**Q: Do I have to read all docs?**  
A: No. Just [01-requirements.md](./01-requirements.md) + [02-architecture.md](./02-architecture.md) § "How It Works"

**Q: How do I add a new event?**  
A: See "Example: Add New Notification" above

**Q: What if recipients need DB query?**  
A: Extend BaseHandler instead of using GenericHandler (see implementation plan)

**Q: Is this really necessary or can I use Laravel Notification?**  
A: Read [02-architecture.md § Why Custom Service](./02-architecture.md#1-design-decisions) (optional, for architects)

---

## 📖 Related Docs

- [Mail Service](../mail-service/readme.md) — Email notifications (different from in-app)
- [Audit System](../audit-system/readme.md) — Audit log (different from notifications)
