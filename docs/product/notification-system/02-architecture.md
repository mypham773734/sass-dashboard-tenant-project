# Notification System — Architecture

**Status:** Planning  
**Level:** Developer who understands basics  
**Purpose:** How the system works, what classes exist, data flow

---

## 🧠 How It Works (Simple Version)

```
1. Code triggers event:
   $notificationService->notifyOne('task.assigned', $tenantId, $userId, $context)

2. Service dispatches job to queue:
   WriteNotificationJob::dispatch(event, tenantId, userId, context)

3. Queue worker runs job:
   - Look up handler class from config
   - Call handler->handle(tenantId, context) → get NotificationDTO
   - Save NotificationDTO to notifications table

4. Frontend polls every 5 seconds:
   - Livewire NotificationBell component
   - Load unread count + latest 10 notifications
   - User clicks bell → dropdown opens

5. User sees notifications + can mark as read
```

**Key point:** All async (via queue) → no blocking

---

## 🏗️ Layer Structure

### Domain Layer (Pure PHP, No Framework)

```
app/Domain/Notification/
├── Entities/
│   └── NotificationEntity.php          ← what a notification is
└── Repositories/
    └── NotificationRepositoryInterface.php  ← contract for DB access
```

**Purpose:** Define WHAT a notification is, independently of database/framework.

---

### Application Layer (Interfaces + DTOs)

```
app/Application/Notification/
├── Contracts/
│   ├── NotificationServiceInterface.php    ← How you USE notifications
│   └── NotificationHandlerInterface.php    ← How events are handled
└── DTOs/
    ├── NotificationDTO.php                 ← data passed between layers
    └── CreateNotificationDTO.php
```

**Purpose:** Define the interface that UseCase depends on (not implementation).

---

### Infrastructure Layer (Implementation + Config)

```
app/Infrastructure/Notifications/
├── NotificationService.php              ← implements interface
├── NullNotificationService.php           ← for tests
├── Handlers/
│   ├── GenericNotificationHandler.php    ← template + config driven
│   ├── BaseNotificationHandler.php       ← abstract base for complex
│   ├── TenantMemberAddedHandler.php      ← extends Base
│   ├── TenantMemberRemovedHandler.php    ← extends Base
│   └── TenantRoleChangedHandler.php      ← extends Base
└── Jobs/
    └── WriteNotificationJob.php          ← queue job

config/notification.php                  ← configuration
```

**Purpose:** Actual implementation (can change without affecting UseCase).

---

### Presentation Layer (UI)

```
resources/views/components/
└── notification-bell.blade.php          ← Livewire component
```

**Purpose:** Show notifications to user.

---

## 🎯 Two Handler Types

### GenericHandler (Simple, 80% of events)

**Use when:** Event has simple title + single/multiple recipients

```php
// config/notification.php
'task.assigned' => [
    'handler'        => GenericNotificationHandler::class,
    'recipients'     => 'assignee_id',        // from context
    'title_template' => '{actor_name} assigned you "{task_title}"',
    'url_template'   => 'task.show:{task_id}',
],
```

**GenericHandler does:**
- Read config
- Render title: `{actor_name}` → Alice
- Resolve recipients: `assignee_id` → 5
- Build URL: `task.show:{task_id}` → /tasks/123
- Save to DB

---

### BaseHandler (Complex, 20% of events)

**Use when:** Need to query DB or complex logic

```php
// Code
class TenantMemberAddedHandler extends BaseNotificationHandler {
    protected string $event = 'tenant.member_added';

    public function __construct(
        private readonly UserRepository $userRepo,
    ) {}

    protected function resolveRecipients(int $tenantId, array $context): array {
        // Query admins from DB
        return $this->userRepo->findAdminsByTenant($tenantId);
    }

    protected function renderTitle(array $context): string {
        return "{$context['new_user_name']} joined the workspace";
    }

    protected function buildUrl(int $tenantId, array $context): string {
        return route('tenant.members');
    }
}

// config/notification.php
'tenant.member_added' => [
    'handler' => TenantMemberAddedHandler::class,  // no template, queries instead
],
```

**BaseHandler does:**
- You override abstract methods:
  - `resolveRecipients()` — query from DB
  - `renderTitle()` — format title
  - `buildUrl()` — create URL
- Parent class `handle()` calls your methods + saves to DB

---

## 📊 Decision Tree: GenericHandler or BaseHandler?

```
New event?

├─ Recipients = single user from context (assignee_id)?
├─ Title = simple template (no if-else)?
├─ URL = route + ID from context?
└─ No DB queries needed?
   YES → GenericHandler (just config)

├─ Need to query DB for recipients?
├─ Conditional title rendering?
├─ Multiple ways to build URL?
└─ Complex logic?
   YES → BaseHandler (create class)
```

**Examples:**
- `task.assigned` → Generic (recipients = assignee_id)
- `task.status_changed` → Generic (recipients = [creator, assignee])
- `tenant.member_removed` → Base (query admins + removed user from DB)
- `tenant.role_changed` → Base (conditional message based on new role)

---

## 📁 Key Classes

### NotificationServiceInterface

```php
interface NotificationServiceInterface {
    // Notify one user
    public function notifyOne(
        string $event,
        int $tenantId,
        int $userId,
        array $context = []
    ): void;

    // Notify multiple users
    public function notify(
        string $event,
        int $tenantId,
        array $recipientIds,
        array $context = []
    ): void;
}
```

**Usage:** Inject into UseCase, call it to trigger notification.

---

### WriteNotificationJob (Queue Job)

```php
class WriteNotificationJob implements ShouldQueue {
    public function __construct(
        private string $event,
        private int $tenantId,
        private int $userId,
        private array $context,
    ) {}

    public function handle(NotificationRepositoryInterface $repo): void {
        // 1. Resolve handler from config
        $handler = $this->resolveHandler();  // GenericHandler or TenantMemberAddedHandler

        // 2. Call handler
        $dto = $handler->handle($this->tenantId, $this->context);

        // 3. Save to DB
        $repo->createForUser($dto, $this->userId, $this->tenantId);
    }
}
```

**Flow:**
- Dispatched by NotificationService (non-blocking)
- Worker picks it up
- Gets handler, calls it, saves result

---

### NotificationHandlerInterface

```php
interface NotificationHandlerInterface {
    // Transform event data into notification
    public function handle(int $tenantId, array $context): NotificationDTO;
}
```

**Implemented by:**
- GenericNotificationHandler
- BaseNotificationHandler (abstract)
- TenantMemberAddedHandler (extends Base)
- etc.

---

### NotificationDTO

```php
class NotificationDTO {
    public function __construct(
        public readonly string $event,           // 'task.assigned'
        public readonly array $recipientIds,     // [5]
        public readonly string $title,           // 'Alice assigned you ...'
        public readonly ?string $body,           // 'Due tomorrow'
        public readonly string $url,             // '/tasks/123'
        public readonly array $data = [],        // ['task_id' => 123]
    ) {}
}
```

**Used to:** Pass data from handler to job.

---

## 🔄 Data Flow: Simple Event

### Example: task.assigned

```
1. Code in UseCase:
   $notificationService->notifyOne('task.assigned', $tenantId, $assigneeId, [
       'task_id'    => 123,
       'task_title' => 'Fix bug',
       'actor_name' => 'Alice',
   ])

2. NotificationService::notifyOne():
   ✅ Check enabled?
   ✅ Check event enabled?
   → WriteNotificationJob::dispatch(...) → queue

3. Queue Worker (async):
   WriteNotificationJob::handle()
   
   a. Resolve handler:
      handler = GenericNotificationHandler  (from config)
   
   b. Call handler:
      $dto = handler->handle($tenantId, $context)
      - Reads config['task.assigned']
      - Renders title: 'Alice assigned you "Fix bug"'
      - Resolves recipients: [5] (from assignee_id)
      - Builds URL: /tasks/123
      → Returns NotificationDTO
   
   c. Save to DB:
      INSERT into notifications (user_id, tenant_id, event, title, url, data, is_read)
      VALUES (5, 3, 'task.assigned', 'Alice assigned you "Fix bug"', '/tasks/123', {...}, false)

4. Frontend (Livewire polling):
   Every 5 seconds:
   - Query: SELECT COUNT(*) FROM notifications WHERE user_id=5 AND tenant_id=3 AND is_read=false
   - Result: 1 unread
   - Update badge: [🔔 1]

5. User clicks bell:
   - Dropdown opens
   - Shows notification: "Alice assigned you Fix bug"
   - Click → redirect to /tasks/123
   - Mark as read
```

---

## 🔄 Data Flow: Complex Event

### Example: tenant.member_removed

```
1. Code in UseCase:
   $notificationService->notify('tenant.member_removed', $tenantId, 
       $adminIds + [$removedUserId],  // multiple recipients
       ['removed_user_name' => 'Bob']
   )

2. NotificationService::notify():
   → For each recipient:
      WriteNotificationJob::dispatch('tenant.member_removed', tenantId, userId, context)

3. Queue Worker (per recipient job):
   WriteNotificationJob::handle()
   
   a. Resolve handler:
      handler = TenantMemberRemovedHandler  (from config)
   
   b. Call handler:
      $dto = handler->handle($tenantId, $context)
      - resolveRecipients() → queries DB → [admin1, admin2, removedUser]
      - renderTitle() → "Bob removed from workspace"
      - buildUrl() → route('tenant.members')
      → Returns NotificationDTO
   
   c. Save to DB:
      (repeated for each recipient job)

4. Database after all jobs done:
   notification 1: admin1 gets notified
   notification 2: admin2 gets notified  
   notification 3: removedUser gets notified
   (same title, same URL, different user_id)

5. Frontend:
   Each user sees their own notifications
```

---

## 🎯 Configuration Driven = Flexibility

### Adding New Simple Event (No Code!)

```php
// config/notification.php
'invoice.approved' => [
    'enabled'           => env('NOTIF_INVOICE_APPROVED', true),
    'handler'           => GenericNotificationHandler::class,
    'recipients'        => 'requester_id',
    'title_template'    => 'Invoice #{invoice_number} approved ✓',
    'body_template'     => 'Amount: ${amount}',
    'url_template'      => 'invoice.show:{invoice_id}',
    'data_keys'         => ['invoice_id', 'invoice_number', 'amount'],
],
```

That's it! No code needed. GenericHandler automatically handles everything.

---

## 🧪 Testing: NullNotificationService

```php
// In test setup:
$this->app->bind(NotificationServiceInterface::class, NullNotificationService::class);

// In test:
public function test_notify_on_task_assign() {
    $null = app(NullNotificationService::class);
    
    $this->useCase->execute($dto, $tenantId);
    
    // Assert notification was sent (no DB, no queue, in-memory only)
    $this->assertTrue($null->assertNotified('task.assigned', $userId));
}
```

**Benefits:**
- No database hit
- No queue needed
- Fast tests
- Can verify exact notifications sent

---

## 🚀 Multi-Tenant Scoping

```
Notification is scoped to BOTH user_id AND tenant_id

User Alice:
  - Tenant 1 (Acme Corp): 3 unread
  - Tenant 2 (Beta Inc): 5 unread
  
When Alice switches tenant:
  → App loads notifications WHERE user_id=alice AND tenant_id=current
  → Badge shows only count for current tenant
  → Dropdown shows only notifications for current tenant
  
This is automatic because:
  1. tenantId always passed explicitly
  2. Repository scopes queries by tenant
  3. Livewire component passes current tenant
```

---

## ⚙️ Why Custom Service (Not Laravel Notification)?

**TLDR:** We need:
- ✅ Clean code (no Eloquent models in UseCase)
- ✅ Multi-tenant built-in (not manual)
- ✅ Config-driven (add events without creating classes)
- ✅ Flexible recipients (query from DB)
- ✅ Good testing (NullService)

Laravel Notification is powerful but doesn't fit these needs well.

👉 **[Full comparison](./readme.md#tại-sao-custom-notificationservice-không-dùng-laravel-notification)** (optional read)

---

## 📚 Next Step

Read [03-implementation-plan.md](./03-implementation-plan.md) to:
- Implement the system step by step
- Code examples for each handler
- What files to create
- Testing strategy
