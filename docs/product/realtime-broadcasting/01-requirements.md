# Real-Time Broadcasting — Requirements

**Status:** Planning  
**Level:** Developer + Architect  
**Purpose:** What must work, driver comparison, constraints

---

## 🎯 Functional Requirements (What Must Work)

### FR1: Instant Notification Delivery
- When notification saved to DB → delivered to user's browser < 1 second
- User sees bell badge update without page reload
- Current: 0-5s latency (polling). New: < 100ms (WebSocket)

### FR2: Only Right User Sees Notifications
- User in tenant A cannot receive notifications for tenant B
- Implemented via channel authorization (`routes/channels.php`)
- Check: user authenticated + user belongs to tenant

### FR3: Unread Count Updates Real-Time
- Badge shows "3" when 3 unread notifications exist
- When user marks as read → badge updates instantly
- When new notification arrives → badge increments instantly

### FR4: Notifications Dropdown Auto-Refreshes
- Click bell → see latest 10 notifications (sorted by created_at DESC)
- New notification appears in dropdown without user refreshing
- Marked notifications show with different style (read vs unread)

### FR5: Backend Broadcasts to Multiple Devices
- User logged in on 2 browsers/devices
- Notification sent → appears on BOTH browsers instantly
- Both show same unread count, same dropdown

### FR6: Queue-Based (Non-Blocking)
- Broadcasting happens asynchronously (in queue job, not request)
- Request to create task returns instantly (task created, broadcast queued)
- Queue worker fires broadcast in background

### FR7: Graceful Fallback (Optional Polling)
- If WebSocket disconnects → can fallback to polling
- Or: Echo auto-reconnects silently
- Must handle: Reverb server offline, network down, browser offline

---

## 🚀 Non-Functional Requirements (Performance, Quality)

| Requirement | Target | Why |
|---|---|---|
| **WebSocket latency** | < 100ms | Feels instant to user |
| **Concurrent connections** | 1,000+ | For scale (future) |
| **Memory per connection** | < 1MB | Efficient server resource |
| **DB query latency** | < 10ms | Notification refresh (with index) |
| **Queue throughput** | 1,000+ notif/sec | Can handle bulk operations |
| **Availability** | Auto-reconnect in 5s | If Reverb restarts |
| **Multi-tenant safety** | Impossible to cross-contaminate | Channel checks at auth level |
| **Test coverage** | Unit: events, Integrated: channels | No regressions |

---

## 🏭 Driver Comparison: Reverb vs Pusher

### Overview

**Both:** Implement Pusher protocol → same code, different backend

| Aspect | Reverb | Pusher |
|--------|--------|--------|
| **Type** | Self-hosted | Managed SaaS |
| **Cost** | $0 (self-hosted) | Free tier (200k msg/month, 100 connections) |
| **Setup** | `php artisan reverb:start` | API keys in `.env` |
| **Scaling** | Horizontal (run multiple) | Automatic (SaaS) |
| **Downtime** | You manage | Pusher uptime SLA |
| **Local dev** | Easy (run on laptop) | Works great |
| **Deploy** | VPS + supervisor | Just `.env` |
| **Data residency** | Your server | Pusher (US/EU) |

### Decision Matrix

| Scenario | Choose | Why |
|---|---|---|
| **Development** | Reverb | Free, instant, no API keys needed |
| **Staging** | Reverb | Mirrors production (if production is Reverb) |
| **Production (MVP)** | Either | Code is identical, choose based on ops comfort |
| **Production (scale >1k users)** | Pusher | Managed, don't worry about server |
| **Production (own VPS)** | Reverb | Save money, full control |

### Technical Details

#### Reverb (Self-Hosted)
```bash
# Setup
php artisan install:broadcasting
# Choose: Reverb

# Run
php artisan reverb:start

# Deploy
# In supervisor config:
# command=php /var/www/app/artisan reverb:start
```

**Pros:**
- ✅ Zero cost
- ✅ No external dependency
- ✅ Instant local dev (no waiting for services)
- ✅ Data stays on your server

**Cons:**
- ❌ You manage WebSocket server uptime
- ❌ Need supervisor + process monitoring
- ❌ Horizontal scaling requires more infra

#### Pusher (SaaS)
```bash
# Setup
php artisan install:broadcasting
# Choose: Pusher

# Deploy
# Just set .env:
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_HOST=api-...
PUSHER_PORT=443
```

**Pros:**
- ✅ Managed (Pusher handles uptime)
- ✅ Auto-scales (no infra to manage)
- ✅ Instant deployment (just `.env`)

**Cons:**
- ❌ Costs money at scale (200k msg/month free, then $0.01/msg)
- ❌ External dependency (Pusher down = your app is slow)
- ❌ Latency: API call to Pusher (slight delay)

### Our Recommendation

**For this project (MVP):**
- **Dev environment:** Reverb (no setup, instant)
- **Production:** Decide later based on:
  - VPS available? → Reverb
  - Want managed service? → Pusher

**Why it doesn't matter now:**
- Code is 100% identical (both implement Pusher protocol)
- Switch later by changing `.env` BROADCAST_CONNECTION and keys
- No code refactoring needed

---

## 🔌 Data Model: Broadcast Event

### NotificationCreated Event

```php
// app/Infrastructure/Notifications/Events/NotificationCreated.php
class NotificationCreated implements ShouldBroadcast
{
    public function __construct(
        public readonly int $notificationId,           // 42
        public readonly int $userId,                  // 5
        public readonly int $tenantId,                // 3
        public readonly string $title,                // "Alice assigned you Task #5"
        public readonly ?string $body,                // null
        public readonly ?string $url,                 // "/tasks/5"
        public readonly \Carbon\Carbon $createdAt,    // timestamp
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenantId}.user.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification-created';  // Event name for Echo listener
    }

    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'title'           => $this->title,
            'url'             => $this->url,
            'created_at'      => $this->createdAt->toIso8601String(),
        ];
    }
}
```

**Key Points:**
- Payload includes only what's needed (title, url) to render notification item
- User ID + Tenant ID build the channel name (authorization)
- Event `ShouldBroadcast` → queued automatically
- Never includes sensitive data (no passwords, secrets)

---

## 🔐 Multi-Tenant Security Model

### Channel Authorization

All channels defined in `routes/channels.php` with strict checks:

```php
Broadcast::channel('tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    // ✅ Only allow if:
    // 1. User is authenticated
    // 2. User ID matches parameter
    // 3. User belongs to tenant (check pivot table)
    
    if ($user->id !== (int)$userId) {
        return false;  // Reject different user
    }

    if (!$user->tenants()->wherePivot('tenant_id', $tenantId)->exists()) {
        return false;  // Reject if not member of tenant
    }

    return $user;  // Allow
});
```

### Tenant Switching

When user switches tenant:
1. Middleware sets `tenantContext()->setId($newTenantId)`
2. Frontend knows to unsubscribe from old channel
3. Frontend subscribes to new channel
4. Old channel: `private-tenant.3.user.5` (leave)
5. New channel: `private-tenant.7.user.5` (join)

Result: User never receives notifications from other tenants (impossible to cross-contaminate)

---

## 🧪 Success Criteria (Test These)

### Functional
- [ ] Notification created → appears on bell in < 1 second
- [ ] User on 2 browsers → both get notification instantly
- [ ] Switch tenant → see only that tenant's notifications
- [ ] Mark as read → badge decrements instantly
- [ ] Unread count accurate (DB match)
- [ ] Click notification → goes to correct resource
- [ ] Queue job broadcasts only to right user

### Security
- [ ] User A can't subscribe to User B's channel
- [ ] User in Tenant A can't see Tenant B's notifications
- [ ] Admin in Tenant A can't send notifications to Tenant B

### Performance
- [ ] WebSocket connection established < 1s
- [ ] Broadcast delivery < 100ms
- [ ] 100 concurrent connections: CPU < 20%

### Resilience
- [ ] Reverb server down → fallback to polling (or graceful degradation)
- [ ] Reconnect after network loss: < 5s
- [ ] Queue job retry on failure: 3 attempts

---

## 🚫 Out of Scope (Not This Phase)

- **Browser push notifications** (separate notification.show() API)
- **Toast popups** (Phase 2: adds visual toast, same broadcasting)
- **Email notifications** (Mail Service handles separately)
- **SMS/WhatsApp** (separate integrations)
- **Notification preferences** (user chooses which events to receive)
- **Grouping** ("5 tasks assigned" vs 5 separate notifications)
- **Notification history** (currently keeps 30 days, not a history page)
- **Presence features** (who's online) → Phase 3

---

## 🔄 Integration Points

### Where Broadcasting Fits

```
Existing System:
├─ Notification entity + repository (already exists)
├─ Notification service (already exists)
├─ WriteNotificationJob (already exists)
└─ NotificationBell Livewire component (exists, uses polling)

NEW: Broadcasting Layer
├─ NotificationCreated event (broadcast signal)
├─ routes/channels.php (authorization)
├─ Laravel Echo setup (frontend)
└─ Livewire listener attribute (reactive)

Result:
└─ Same data flow, but with instant real-time update
```

### Queue Integration

Broadcasting uses same queue as notifications:
```
WriteNotificationJob
├─ Save to DB (synchronous)
└─ Dispatch NotificationCreated (queued broadcast)
  └─ Reverb/Pusher delivers to user's browser
```

No extra infrastructure needed (reuses existing queue).

---

## 📚 Next Step

Read [02-architecture.md](./02-architecture.md) to understand:
- How channels and events work together
- Where to place broadcast event in codebase
- Frontend listener implementation
- Tenant switching + channel unsubscribe
