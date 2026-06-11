# Real-Time Broadcasting — Architecture

**Status:** Planning  
**Level:** Architect + Senior Developer  
**Purpose:** How components interact, design decisions, implementation details

---

## 🏗️ Layer Structure (Clean Architecture)

### How Broadcasting Fits Into Existing Layers

```
HTTP/Presentation ← Controllers, Livewire components
     ↓
Application       ← NotificationService (already exists, no change)
     ↓
Domain            ← Notification entity, repository interface (no change)
     ↓
Infrastructure    ← NEW: NotificationCreated event, Reverb config
     ↓
WebSocket Bridge  ← Reverb/Pusher server
```

### Why Broadcasting in Infrastructure?

✅ **NotificationCreated event** uses Laravel's `ShouldBroadcast` interface → Infrastructure only  
✅ **Dependency:** Relies on Laravel Broadcasting package (not pure PHP)  
✅ **Reusability:** Domain/Application layers stay framework-agnostic  
✅ **Testing:** Can mock broadcast events without Laravel dependencies  

### No Changes Needed
- Domain entities: Stay pure
- Repository interface: Stay clean
- Use cases: No new code
- DTOs: Unchanged

---

## 🔀 Broadcast Event Design

### NotificationCreated Event (Infrastructure Layer)

**File location:** `app/Infrastructure/Notifications/Events/NotificationCreated.php`

```php
namespace App\Infrastructure\Notifications\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public readonly int $notificationId,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $url,
        public readonly \Carbon\Carbon $createdAt,
    ) {}

    // Which channel to broadcast on
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenantId}.user.{$this->userId}"),
        ];
    }

    // Event name for JavaScript listener
    public function broadcastAs(): string
    {
        return 'notification-created';
    }

    // Data payload sent to frontend
    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'title'           => $this->title,
            'body'            => $this->body,
            'url'             => $this->url,
            'created_at'      => $this->createdAt->toIso8601String(),
        ];
    }

    // Queue name (reuse notification queue)
    public function broadcastQueue(): string
    {
        return 'notifications';
    }
}
```

**Why these fields?**
- `notificationId`, `title`, `url` → render notification item in UI
- `tenantId`, `userId` → build channel name for authorization
- Payload is **minimal** → fast, not exposing unnecessary data

### Where to Fire the Event

**File:** `app/Infrastructure/Notifications/Jobs/WriteNotificationJob.php`

```php
namespace App\Infrastructure\Notifications\Jobs;

use App\Infrastructure\Notifications\Events\NotificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Queueable;

class WriteNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly CreateNotificationDTO $dto,
        private readonly int $userId,
        private readonly int $tenantId,
    ) {}

    public function handle(NotificationRepositoryInterface $repository)
    {
        // Step 1: Save notification to DB
        $entity = $repository->createForUser($this->dto, $this->userId, $this->tenantId);

        // Step 2: Broadcast event to user
        // ✅ NOW we fire the broadcast event
        // ✅ It queues automatically (ShouldBroadcast)
        broadcast(new NotificationCreated(
            notificationId: $entity->id,
            userId:         $this->userId,
            tenantId:       $this->tenantId,
            title:          $entity->title,
            body:           $entity->body,
            url:            $entity->url,
            createdAt:      Carbon::parse($entity->createdAt),
        ));
    }
}
```

**Key points:**
- Broadcast happens AFTER DB save (consistent state)
- `broadcast()` helper queues the broadcast in background
- If broadcast fails, notification still in DB (doesn't matter, polling fallback)
- No blocking → request completes fast

---

## 🔐 Channel Authorization (Multi-Tenant Security)

### Define Channels

**File:** `routes/channels.php` (created by `php artisan install:broadcasting`)

```php
use Illuminate\Support\Facades\Broadcast;

// Private channel: only the specific user can subscribe
Broadcast::channel('tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    // Verify: 
    // 1. User is authenticated ($user is set)
    // 2. User ID matches route parameter
    if ((int)$userId !== $user->id) {
        return false;  // Reject if different user
    }

    // 3. User belongs to the tenant
    $belongs = $user->tenants()
        ->wherePivot('tenant_id', $tenantId)
        ->exists();

    return $belongs ? $user : false;
});

// For future chat feature:
// Broadcast::channel('tenant.{tenantId}.conversation.{conversationId}', ...)
// Broadcast::presence('tenant.{tenantId}', ...)  ← who's online
```

### How Channel Authorization Works

```
Frontend tries to subscribe:
  Echo.private(`tenant.3.user.5`)
    ↓
Backend checks /broadcasting/auth endpoint:
  ├─ Get authenticated user (via session)
  ├─ Extract tenantId=3, userId=5 from channel name
  ├─ Call channel callback with ($user, 3, 5)
  ├─ Check: user.id === 5? ✅
  ├─ Check: user belongs to tenant 3? ✅
  └─ Return true → authorize
    ↓
Frontend subscribed ✅
  Now receives NotificationCreated events on this channel
```

### Multi-Tenant Isolation Guaranteed

**Scenario 1: Hacker tries to listen to other user's channel**
```
Frontend tries: Echo.private(`tenant.3.user.7`)
  ↓
Backend checks: Is current user ID 7? NO (user is 5)
  ↓
Reject ❌ (return false)
  ↓
Frontend does NOT subscribe
  ↓
User 5 never receives events intended for User 7
```

**Scenario 2: User in Tenant A tries to see Tenant B's notifications**
```
Frontend tries: Echo.private(`tenant.999.user.5`)
  ↓
Backend checks: 
  ├─ User ID 5? ✅
  └─ User 5 belongs to tenant 999? ❌ (only member of tenant 3)
  ↓
Reject ❌
  ↓
No cross-tenant notification leak
```

---

## 🎧 Frontend Listener (Livewire)

### NotificationBell Component

**File:** `app/Livewire/NotificationBell.php` (existing, enhanced)

```php
namespace App\Livewire;

use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    private readonly NotificationRepositoryInterface $repository;
    public array $notifications = [];
    public int $unreadCount = 0;
    public bool $showDropdown = false;

    // Constructor injection
    public function __construct(NotificationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function mount()
    {
        $this->refresh();
    }

    // ✅ NEW: Listen to broadcast event
    // When NotificationCreated fires on our channel, this runs
    #[On('echo-private:tenant.{tenantId}.user.{userId},notification-created')]
    public function onNotificationCreated($data)
    {
        // Data includes: notification_id, title, url, created_at
        $this->refresh();  // Refresh from DB to get latest
    }

    // Refresh from database
    public function refresh()
    {
        $tenantId = tenantContext()->getId();
        $userId   = auth()->id();

        $this->notifications = $this->repository->getUnreadByUser($userId, $tenantId, 10);
        $this->unreadCount   = $this->repository->countUnreadByUser($userId, $tenantId);
    }

    public function markRead($notificationId)
    {
        $this->repository->markAsRead($notificationId, auth()->id());
        $this->refresh();
    }

    public function markAllAsRead()
    {
        $tenantId = tenantContext()->getId();
        $this->repository->markAllAsRead(auth()->id(), $tenantId);
        $this->refresh();
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
```

### Key Changes vs Old Version

| Old (Polling) | New (Real-Time) |
|---|---|
| `wire:poll.5s="refresh"` | `#[On('echo-private:...,notification-created')]` |
| Polls every 5s | Only refreshes when event fires |
| Many unnecessary DB hits | Only queries when needed |
| Up to 5s delay | < 100ms delay |

### Blade View (Unchanged Logic, Same Rendering)

**File:** `resources/views/livewire/notification-bell.blade.php`

```blade
<div class="notification-bell">
    <!-- Bell icon with badge -->
    <button wire:click="toggleDropdown" class="relative">
        <svg class="h-6 w-6"><!-- bell icon --></svg>
        @if ($unreadCount > 0)
            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown (toggled) -->
    @if ($showDropdown)
        <div class="dropdown">
            <div class="header">
                Notifications
                @if ($unreadCount > 0)
                    <button wire:click="markAllAsRead">Mark All ✓</button>
                @endif
            </div>

            @if ($notifications)
                @foreach ($notifications as $notif)
                    <div 
                        wire:click="markRead({{ $notif['id'] }})"
                        class="notification-item {{ $notif['is_read'] ? '' : 'unread' }}"
                    >
                        <span class="dot">{{ $notif['is_read'] ? '' : '●' }}</span>
                        <span class="title">{{ $notif['title'] }}</span>
                    </div>
                @endforeach
            @else
                <p class="empty">No notifications</p>
            @endif

            <div class="footer">
                <a href="{{ route('notifications.index') }}">View All</a>
            </div>
        </div>
    @endif
</div>
```

---

## 🌐 Echo Configuration (Frontend)

### Setup (Create file)

**File:** `resources/js/echo.js` (created by `php artisan install:broadcasting`)

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Choose driver (from config/broadcasting.php)
const BROADCAST_DRIVER = import.meta.env.VITE_BROADCAST_DRIVER || 'reverb';

const echoConfig = {
    broadcaster: 'pusher',  // Both Reverb and Pusher use Pusher protocol
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    wsHost: BROADCAST_DRIVER === 'reverb' 
        ? import.meta.env.VITE_REVERB_HOST 
        : import.meta.env.VITE_PUSHER_HOST,
    wsPort: BROADCAST_DRIVER === 'reverb'
        ? import.meta.env.VITE_REVERB_PORT
        : import.meta.env.VITE_PUSHER_PORT,
    wssPort: BROADCAST_DRIVER === 'reverb'
        ? import.meta.env.VITE_REVERB_PORT
        : import.meta.env.VITE_PUSHER_PORT,
    forceTLS: BROADCAST_DRIVER === 'reverb' ? false : true,
    enabledTransports: BROADCAST_DRIVER === 'reverb' ? ['ws', 'wss'] : ['ws', 'wss'],
};

window.Echo = new Echo(echoConfig);
```

### Import in Bootstrap

**File:** `resources/js/bootstrap.js`

```javascript
// Existing axios setup...
import './echo.js';  // ← Add this line
```

### Environment Variables

**File:** `.env` (add these based on driver choice)

```bash
# For Reverb (development)
BROADCAST_CONNECTION=reverb
VITE_BROADCAST_DRIVER=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# For Pusher (production)
# BROADCAST_CONNECTION=pusher
# PUSHER_APP_ID=...
# PUSHER_APP_KEY=...
# PUSHER_APP_SECRET=...
# PUSHER_HOST=api-...
# VITE_PUSHER_APP_KEY=...
# VITE_PUSHER_APP_CLUSTER=...
```

---

## 🔄 Tenant Switching Flow

### Challenge: User Switches Tenant

```
User in Tenant 3:
  Echo subscribed to: private-tenant.3.user.5
  ✅ Receives notifications for tenant 3

User clicks "Switch to Tenant 7":
  ↓ Middleware: tenantContext()->setId(7)
  ↓ Page reloads / component remounts
  ↓ Livewire NotificationBell remounts
  ↓ What should happen?
```

### Solution: Livewire Auto-Resubscribe

When `NotificationBell` component remounts (new tenant):

```php
public function mount()
{
    $tenantId = tenantContext()->getId();  // Now 7
    $userId   = auth()->id();               // Still 5
    
    // ✅ Livewire listener automatically updates channel name
    // Old subscription: private-tenant.3.user.5 (automatically unsubscribed)
    // New subscription: private-tenant.7.user.5 (automatically subscribed)
    
    $this->refresh();  // Get notifications for new tenant
}
```

**How Livewire handles this:**
1. Old component destroyed (unsubscribe from `private-tenant.3.user.5`)
2. New component mounted (subscribe to `private-tenant.7.user.5`)
3. Echo handles channel switch automatically

**Result:** Transparent to developer! Just remount the component.

---

## 📊 Data Flow: Complete Picture

```
1. UseCase creates notification
   ↓
   $this->notificationService->notifyOne(
       event: 'task.assigned',
       userId: 5,
       tenantId: 3,
       context: [...]
   )

2. NotificationService dispatches WriteNotificationJob
   ↓
   WriteNotificationJob::dispatch($dto, $userId, $tenantId)

3. Queue worker processes job
   ├─ Repository saves to DB
   │  └─ INSERT notifications (user_id=5, tenant_id=3, ...)
   │
   └─ broadcast(new NotificationCreated(...))
      └─ Event queued to notifications queue

4. Broadcasting worker processes event
   ├─ Build channel: "tenant.3.user.5"
   ├─ Serialize payload (id, title, url)
   └─ Send to Reverb/Pusher server

5. Reverb/Pusher route to subscribers
   └─ Find all connections on "tenant.3.user.5"
   └─ Send event "notification-created" with payload

6. Browser receives WebSocket message
   └─ Echo listener detects "notification-created"
   └─ Calls NotificationBell#onNotificationCreated($data)

7. Livewire reactive refresh
   ├─ onNotificationCreated() calls refresh()
   ├─ refresh() queries DB: notifications for user 5, tenant 3
   ├─ Updates $notifications, $unreadCount
   └─ Re-renders view (bell badge, dropdown)

8. User sees update
   └─ Bell badge changes: [🔔] → [🔔 1]
   └─ New notification in dropdown
   └─ All < 100ms from event fire
```

---

## 🧪 Testing Considerations

### Unit Tests
```php
// Test that NotificationCreated event formats correctly
test('notification created event broadcasts correct data', function () {
    $event = new NotificationCreated(...);
    
    expect($event->broadcastWith())
        ->toHaveKeys(['notification_id', 'title', 'url']);
});

// Test channel authorization
test('user cannot subscribe to other user channel', function () {
    // Try to authorize tenant.3.user.7 as user 5
    // Should return false
});
```

### Integration Tests
```php
// Test full flow
test('notification triggers instant update in Livewire', function () {
    // Create task → notification created
    // Assert: NotificationBell component receives broadcast
    // Assert: badge count updated in view
});
```

### Manual Testing
- See [03-implementation-plan.md](./03-implementation-plan.md#verification)

---

## 🚀 Extensibility: Future Features

### Reuse for Chat (Phase 3)

Same architecture, new channels:

```php
// Chat conversation channel
Broadcast::channel('tenant.{tenantId}.conversation.{conversationId}', function ($user, $tenantId, $conversationId) {
    // Check: user belongs to tenant + conversation member
    return $user->isMemberOfConversation($conversationId);
});

// Chat presence (who's online)
Broadcast::presence('tenant.{tenantId}', function ($user, $tenantId) {
    // Check: user belongs to tenant
    return $user->tenants()->where('tenant_id', $tenantId)->exists();
});
```

**Cost:** Minimal! Same Echo + Reverb infrastructure.

---

## 📚 Related Concepts

- **Queues:** [commands.md](../../.claude/rules/commands.md#queue) — How to run queue workers
- **Clean Architecture:** [architecture.md](../../.claude/rules/architecture.md) — Why event in Infrastructure
- **Livewire:** [04-implementation-plan.md](./03-implementation-plan.md) — How to test component listener

