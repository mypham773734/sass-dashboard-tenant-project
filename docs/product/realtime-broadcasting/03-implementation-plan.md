# Real-Time Broadcasting — Implementation Plan

**Status:** Planning  
**Level:** Developer + DevOps  
**Purpose:** Step-by-step checklist to implement real-time notifications

---

## 📋 Overview: 4 Phases

```
Phase 1: Setup & Config       ← Infrastructure
Phase 2: Backend Events       ← Application layer
Phase 3: Frontend Listener    ← Presentation layer
Phase 4: Verification & Test  ← Quality assurance
```

**Estimated effort:** 3-4 development sessions (2-3 hours each)

---

## Phase 1: Setup & Infrastructure

### Goal
Set up Laravel Broadcasting, choose driver, install npm packages

### Checklist

#### Step 1.1: Install Broadcasting Scaffolding
```bash
php artisan install:broadcasting

# Choose: Reverb (for development)
# This creates:
# ├─ config/broadcasting.php
# ├─ routes/channels.php
# └─ resources/js/echo.js
```

**What it does:**
- Creates `config/broadcasting.php` (driver selection)
- Creates `routes/channels.php` (channel authorization)
- Creates `resources/js/echo.js` (frontend setup)
- Publishes BroadcastServiceProvider
- Updates .env with BROADCAST_CONNECTION

#### Step 1.2: Install npm Packages
```bash
npm install laravel-echo pusher-js
```

#### Step 1.3: Configure for Development (Reverb)
**File:** `.env`
```bash
BROADCAST_CONNECTION=reverb
VITE_BROADCAST_DRIVER=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### Step 1.4: Verify Installation
```bash
# Terminal 1: Run Reverb server
php artisan reverb:start

# Terminal 2: Check it's listening
curl -i http://127.0.0.1:8080

# Should return: 200 OK
```

#### Step 1.5: Configure for Production (Choose Later)
For production, update `.env` based on driver:

**Option A: Reverb (Self-hosted)**
```bash
BROADCAST_CONNECTION=reverb
REVERB_HOST=your-vps.com
REVERB_PORT=8080
REVERB_SCHEME=https  # Important: use HTTPS in production
```

Then in supervisor:
```ini
[program:laravel-reverb]
process_name=%(program_name)s
command=php /var/www/app/artisan reverb:start
numprocs=1
stdout_logfile=/var/log/reverb.log
stderr_logfile=/var/log/reverb.log
autorestart=true
```

**Option B: Pusher (SaaS)**
```bash
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=api-xx.pusher.com
PUSHER_PORT=443

# Also set Vite env vars
VITE_PUSHER_APP_KEY=your_app_key
VITE_PUSHER_APP_CLUSTER=us3
```

### Success Criteria
- [ ] `php artisan reverb:start` runs without errors
- [ ] `npm run build` completes without warnings
- [ ] `.env` has BROADCAST_CONNECTION set
- [ ] No git conflicts in config/broadcasting.php

**Effort:** 15-20 minutes

---

## Phase 2: Backend — Events & Broadcasting

### Goal
Create NotificationCreated event, fire it from WriteNotificationJob

### Checklist

#### Step 2.1: Create Broadcast Event
**File:** `app/Infrastructure/Notifications/Events/NotificationCreated.php`

```php
<?php

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

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenantId}.user.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification-created';
    }

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

    public function broadcastQueue(): string
    {
        return 'notifications';
    }
}
```

**Key points:**
- Extends `ShouldBroadcast` → auto-queued
- Channel: `tenant.{tenantId}.user.{userId}` → scoped per user per tenant
- Event name: `'notification-created'` → matches frontend listener
- Queue name: `'notifications'` → reuses existing queue

**Test it:** Run tests to ensure event serializes correctly

#### Step 2.2: Update WriteNotificationJob
**File:** `app/Infrastructure/Notifications/Jobs/WriteNotificationJob.php`

Add broadcast dispatch after DB save:

```php
<?php

namespace App\Infrastructure\Notifications\Jobs;

use App\Infrastructure\Notifications\Events\NotificationCreated;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Application\Notification\DTOs\NotificationDTO;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Queueable;

class WriteNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly NotificationDTO $dto,
        private readonly int $userId,
        private readonly int $tenantId,
    ) {}

    public function handle(NotificationRepositoryInterface $repository): void
    {
        // Step 1: Save to DB
        $entity = $repository->createForUser(
            $this->dto,
            $this->userId,
            $this->tenantId
        );

        // Step 2: Broadcast to user
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
- Broadcast AFTER DB save (ensures consistency)
- `broadcast()` helper queues automatically
- No changes to NotificationService (still just dispatch job)

**Test it:**
```bash
php artisan tinker

# Manually trigger job
>>> use App\Infrastructure\Notifications\Jobs\WriteNotificationJob;
>>> use App\Application\Notification\DTOs\NotificationDTO;

>>> $dto = new NotificationDTO(
    event: 'task.assigned',
    title: 'Test',
    body: null,
    url: '/tasks/1',
    data: []
);

>>> WriteNotificationJob::dispatch($dto, 5, 3);
# Should see broadcast logged (BROADCAST_CONNECTION=log during test)
```

#### Step 2.3: Define Channel Authorization
**File:** `routes/channels.php`

Add private channel for notifications:

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

Broadcast::channel('tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    // Verify user ID matches
    if ((int)$userId !== $user->id) {
        return false;
    }

    // Verify user belongs to tenant
    $belongsToTenant = $user->tenants()
        ->wherePivot('tenant_id', (int)$tenantId)
        ->exists();

    return $belongsToTenant ? $user : false;
});

// Future: Chat channel
// Broadcast::channel('tenant.{tenantId}.conversation.{conversationId}', ...)

// Future: Presence
// Broadcast::presence('tenant.{tenantId}', ...)
```

**Key points:**
- `(int)` cast route parameters (they come as strings)
- Check BOTH user ID and tenant membership
- Return `$user` object if authorized, `false` if not

**Test it:**
```bash
# In debug mode, test channel authorization
php artisan tinker

>>> $user = User::find(5);
>>> $user->tenants; // Should show tenant 3
>>> // Then test channel auth in browser DevTools (see verification phase)
```

### Success Criteria
- [ ] `NotificationCreated` event created, implements `ShouldBroadcast`
- [ ] `WriteNotificationJob` fires `broadcast(...)` after save
- [ ] `routes/channels.php` has private channel with auth check
- [ ] Tests pass (event serialization, channel auth logic)
- [ ] Manual tinker test: job dispatches, broadcast logs

**Effort:** 30-40 minutes

---

## Phase 3: Frontend — Listener & Echo

### Goal
Update NotificationBell component to listen for real-time events

### Checklist

#### Step 3.1: Verify Echo Initialization
**File:** `resources/js/bootstrap.js`

Ensure echo.js is imported:

```javascript
// ... existing axios setup ...

import './echo.js';  // ← This line must exist
```

#### Step 3.2: Update NotificationBell Component
**File:** `app/Livewire/NotificationBell.php`

Add the listener attribute:

```php
<?php

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

    public function __construct(NotificationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function mount()
    {
        $this->refresh();
    }

    // ✅ NEW: Listen for broadcast event
    #[On('echo-private:tenant.{tenantId}.user.{userId},notification-created')]
    public function onNotificationCreated($data)
    {
        // Refresh from DB to get latest state
        $this->refresh();
    }

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

**Key changes:**
- Line: `#[On('echo-private:tenant.{tenantId}.user.{userId},notification-created')]` — listens to broadcast
- Method `onNotificationCreated($data)` — called when event fires
- Still calls `refresh()` to query DB (ensures consistency, simple)

**Important:** Livewire 4 automatically interpolates `{tenantId}` and `{userId}` from component properties or context. Make sure they're accessible.

#### Step 3.3: Optional — Remove or Reduce Polling
**File:** `resources/views/livewire/notification-bell.blade.php`

OLD (if it exists):
```blade
<div wire:poll.5s="refresh" class="notification-bell">
    <!-- ... -->
</div>
```

NEW (polling no longer needed):
```blade
<div class="notification-bell">
    <!-- ... -->
</div>
```

OR keep polling as fallback (every 60s):
```blade
<div wire:poll.60s="refresh" class="notification-bell">
    <!-- ... -->
</div>
```

**Recommendation:** Remove polling entirely. Broadcasting is reliable; if it fails, user reloads page anyway.

#### Step 3.4: Build Frontend Assets
```bash
npm run build
```

### Success Criteria
- [ ] `onNotificationCreated` method exists on NotificationBell
- [ ] `#[On(...)]` attribute with correct channel name
- [ ] `npm run build` succeeds
- [ ] No TypeScript/JavaScript errors in browser console
- [ ] Vite imports echo.js correctly

**Effort:** 15-20 minutes

---

## Phase 4: Verification & Testing

### Goal
Confirm real-time delivery works end-to-end

### Checklist

#### Step 4.1: Manual Test (Single User, Single Browser)

**Setup:**
```bash
# Terminal 1: Start Reverb server
php artisan reverb:start

# Terminal 2: Start queue worker
php artisan queue:listen

# Terminal 3: Start dev server
php artisan serve

# Terminal 4: Watch logs
php artisan pail

# Browser: Open http://127.0.0.1:8000 and login
```

**Test:**
1. Open dashboard, look at notification bell
2. In tinker, manually dispatch job:
   ```bash
   php artisan tinker
   >>> use App\Infrastructure\Notifications\Jobs\WriteNotificationJob;
   >>> $dto = new App\Application\Notification\DTOs\NotificationDTO(...);
   >>> WriteNotificationJob::dispatch($dto, auth()->id(), tenantContext()->getId());
   ```
3. **Expected:** Notification appears in bell < 1 second, NO page refresh
4. Check logs in terminal 4 — should see broadcast event

#### Step 4.2: Manual Test (Two Users, Two Browsers)

**Setup:**
- Browser 1: User 5, logged in
- Browser 2: User 7, logged in (same tenant 3)

**Test:**
1. In tinker, create notification for User 5:
   ```php
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```
2. **Expected:** Bell updates ONLY in Browser 1 (User 5)
3. Bell does NOT update in Browser 2 (User 7)
4. Create notification for User 7:
   ```php
   >>> WriteNotificationJob::dispatch($dto, 7, 3);
   ```
5. **Expected:** Bell updates ONLY in Browser 2 (User 7)

**✅ Multi-user isolation confirmed**

#### Step 4.3: Manual Test (Tenant Switching)

**Setup:**
- User 5 is member of both Tenant 3 and Tenant 7

**Test:**
1. Browser: Switch to Tenant 3 (dropdown menu)
2. Tinker: Create notification for Tenant 3:
   ```php
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```
3. **Expected:** Bell updates
4. Browser: Switch to Tenant 7
5. Tinker: Create notification for Tenant 3 (old tenant):
   ```php
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```
6. **Expected:** Bell does NOT update (user in different tenant now)
7. Tinker: Create notification for Tenant 7 (new tenant):
   ```php
   >>> WriteNotificationJob::dispatch($dto, 5, 7);
   ```
8. **Expected:** Bell updates (correct tenant)

**✅ Tenant switching + scoping confirmed**

#### Step 4.4: Stress Test (Reverb Stability)

**Setup:**
```bash
# Terminal: Monitor Reverb logs
php artisan reverb:start --debug

# Tinker: Dispatch 100 notifications rapidly
```

**Test:**
```php
>>> for ($i = 1; $i <= 100; $i++) {
    WriteNotificationJob::dispatch($dto, 5, 3);
}
```

**Expected:**
- All 100 appear in bell dropdown
- Badge shows 100 unread
- No WebSocket disconnects
- Reverb handles load gracefully

#### Step 4.5: Fallback Test (WebSocket Downtime)

**Setup:**
- Reverb running
- Browser connected, notification bell working

**Test:**
1. Terminal: Stop Reverb server (Ctrl+C)
2. Browser: Create new notification via UI (task assignment, etc.)
3. **Expected:** Notification does NOT appear immediately (WebSocket down)
4. Browser: Reload page manually
5. **Expected:** Notification appears (polling fallback or fresh load)
6. Terminal: Restart Reverb server
7. Create new notification
8. **Expected:** Bell updates in < 1 second (WebSocket restored)

**Note:** If you removed polling entirely, reload is user's only option during downtime. That's acceptable (rare).

#### Step 4.6: Automated Tests (PHPUnit)

Create test file: `tests/Feature/Notifications/BroadcastNotificationTest.php`

```php
<?php

namespace Tests\Feature\Notifications;

use App\Infrastructure\Notifications\Events\NotificationCreated;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastNotificationTest extends TestCase
{
    #[Test]
    public function notification_created_event_broadcasts_to_correct_channel(): void
    {
        $event = new NotificationCreated(
            notificationId: 42,
            userId:         5,
            tenantId:       3,
            title:          'Test notification',
            body:           null,
            url:            '/test',
            createdAt:      Carbon::now(),
        );

        // Assert channel name
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertStringContainsString('tenant.3.user.5', $channels[0]->name);
    }

    #[Test]
    public function notification_created_event_has_correct_payload(): void
    {
        $event = new NotificationCreated(...);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('notification_id', $payload);
        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('url', $payload);
    }
}
```

Run:
```bash
php artisan test tests/Feature/Notifications/BroadcastNotificationTest.php
```

### Success Criteria Checklist

- [ ] Single user: notification appears < 1 second
- [ ] Multi-user: only correct user sees notification
- [ ] Tenant 1: sees only Tenant 1 notifications
- [ ] Tenant 2: sees only Tenant 2 notifications
- [ ] Switch tenant: notifications scoped correctly
- [ ] Reverb handles 100+ concurrent notifications
- [ ] Fallback works (reload restores notifications)
- [ ] Automated tests pass
- [ ] No console errors in browser DevTools
- [ ] No database queries visible in `php artisan pail` during broadcast

**Effort:** 1-2 hours (manual + automated testing)

---

## 🎯 Success Criteria (Overall)

### Must Have
- ✅ Real-time delivery: notification appears < 100ms from event fire
- ✅ Multi-tenant isolation: user can ONLY see their own tenant's notifications
- ✅ Graceful fallback: works without WebSocket (polling or reload)
- ✅ No regressions: existing features unaffected

### Nice to Have
- ✅ Stress tested: 100+ concurrent notifications
- ✅ Two-browser test: different users, same tenant
- ✅ Automated tests: broadcast events tested
- ✅ Documentation: this plan completed, code has comments

---

## 🆘 Troubleshooting

### Problem: WebSocket connection fails
```
Error: ws://localhost:8080/socket.io/?EIO=4&TRANSPORT=websocket
```

**Fixes:**
1. Ensure Reverb running: `php artisan reverb:start`
2. Check firewall: port 8080 open
3. Check `.env` REVERB_HOST/PORT match
4. Restart npm build: `npm run build`

### Problem: Notifications don't broadcast
```
Notification appears in DB but not in UI
```

**Fixes:**
1. Ensure queue worker running: `php artisan queue:listen`
2. Check `routes/channels.php` authorization logic
3. Verify Livewire listener attribute: `#[On('echo-...')]`
4. Check browser console for errors
5. Look at `php artisan pail` for queue errors

### Problem: User sees other tenant's notifications
```
User in Tenant 3 sees Tenant 5 notifications
```

**Fixes:**
1. Check `routes/channels.php` — verify tenant membership check
2. Verify `tenantContext()->getId()` returns correct tenant
3. Test channel auth: `php artisan tinker` → call Broadcast::channel manually
4. Check ChooseCurrentTenant middleware runs on every request

### Problem: 100s of missed notifications after restart
```
User was offline for 1 hour, comes back to 100 unread notifications
```

**Expected behavior:** Working as designed!
- Notifications stored in DB permanently
- User sees all unread on bell refresh
- If want to limit: implement notification cleanup job (30-day retention)

---

## 📚 Related Commands

```bash
# Queue management
php artisan queue:listen
php artisan queue:failed
php artisan queue:flush

# Reverb management
php artisan reverb:start --debug
php artisan reverb:metrics

# Cache (if needed)
php artisan cache:clear
php artisan config:clear

# Supervisor (if using Reverb in production)
sudo supervisorctl status laravel-reverb
sudo supervisorctl restart laravel-reverb
sudo supervisorctl stop laravel-reverb
```

---

## ✅ Sign-Off Checklist (Ready to Ship)

- [ ] All 4 phases complete
- [ ] Phase 4 verification tests pass
- [ ] Code review: architecture follows patterns.md
- [ ] Tests written + passing
- [ ] Documentation updated (this file)
- [ ] No git conflicts or merge issues
- [ ] `.env.example` updated with broadcasting variables
- [ ] README updated with "how to run Reverb" instruction

---

**Next:** After implementation, update the related docs:
- [notification-system/readme.md](../notification-system/readme.md) — link to real-time docs
- Project PR description with links to this plan
