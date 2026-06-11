# Real-Time Broadcasting

**What:** WebSocket infrastructure for instant notifications + future chat  
**Status:** Planning  
**For:** New developers + architects — understand why real-time matters

---

## 🎯 Problem We're Solving

### Current State (Polling)

```
1. Notification created in DB
   ↓
2. User's browser polls every 5 seconds
   ↓
3. "Any new notifs?" → API checks DB
   ↓
4. Browser gets update (up to 5s delay)
   ↓
5. User sees it (SLOW ❌)
```

**Issues:**
- **Latency:** 0-5s delay feels slow to users
- **Server load:** 100 users × 12 polls/min = constant DB hits
- **Not scalable:** More users = more polling = more DB strain

### New State (Real-Time with WebSocket)

```
1. Notification created in DB
   ↓
2. Backend broadcasts "notification arrived" via WebSocket
   ↓
3. User's browser receives event instantly (< 100ms)
   ↓
4. Frontend updates bell UI automatically
   ↓
5. User sees it (FAST ✅)
```

**Benefits:**
- **Instant:** Real-time delivery (< 100ms)
- **Efficient:** No polling overhead
- **Scalable:** 1,000s of concurrent users
- **Future-proof:** Foundation for real-time chat

---

## 💡 How It Works (30-Second Overview)

### Tech Stack

```
Backend                    WebSocket Bridge        Frontend
─────────                  ────────────────        ────────

Notification              Laravel Reverb            JavaScript
  created ────→  broadcast  (WebSocket server)  ───→  Echo listener
                  event                              ↓
                                              Update notification bell
                                              Show unread count
```

### Three Key Components

| Component | Purpose | Where | Example |
|-----------|---------|-------|---------|
| **Broadcast Event** | Sends signal to WebSocket | `app/Infrastructure/Notifications/Events/` | `NotificationCreated` |
| **Channel** | Controls who receives (routing) | `routes/channels.php` | `private-tenant.3.user.5` |
| **Echo Listener** | Frontend listens + reacts | `NotificationBell.php` | `#[On('echo-private:...,NotificationCreated')]` |

---

## 🔄 Data Flow: From Create to Display

```
UseCase: CreateTaskUseCase
  ├─ Tasks created in DB
  └─ Calls notificationService->notifyOne(...)
           ↓
NotificationService
  └─ Dispatches WriteNotificationJob to queue
           ↓
Queue Worker processes WriteNotificationJob
  ├─ Resolves handler from config
  ├─ Saves notification to DB
  └─ Broadcasts NotificationCreated event ← NEW!
           ↓
Laravel Reverb (WebSocket server)
  └─ Routes event to channel: private-tenant.3.user.5
           ↓
User's Browser (Laravel Echo)
  ├─ Receives event on WebSocket
  └─ Triggers Livewire listener
           ↓
NotificationBell Component
  ├─ Calls refresh() from DB
  ├─ Updates badge count
  └─ Updates dropdown (INSTANT)
```

---

## 🏗️ Quick Architecture (Mental Model)

### Layers Involved

```
HTTP/Controllers  ← App's controllers (UseCase, etc.)
     ↓
Application       ← NotificationService (already exists)
     ↓
Infrastructure    ← NEW: NotificationCreated event + Reverb config
     ↓
WebSocket Bridge  ← NEW: Reverb server + channels.php
     ↓
Frontend          ← Echo client + Livewire listener
```

### Clean Architecture Rules (We Follow)

✅ **Domain**: No changes (pure business logic stays pure)  
✅ **Application**: No changes (UseCase stays clean)  
✅ **Infrastructure**: Add `NotificationCreated` event here (Laravel-specific)  
✅ **Http**: Livewire component gets listener (reactive)  

---

## 🎯 What This Enables (Roadmap)

### Phase 1 (Current Plan)
- Real-time notification bell
- Instant unread badge update
- Multi-tenant isolation at channel level

### Phase 2 (Future)
- Real-time mentions in comments
- Project activity updates (task created, status changed)
- Notification toast popups (not just bell)

### Phase 3+ (Planned in system)
- Chat system (same channel infrastructure!)
- Presence awareness (who's online)
- Collaborative editing

**Key insight:** Reverb channel infrastructure becomes reusable backbone for chat, presence, and more.

---

## 🚀 Reading Path for Developers

### Path 1: "Show me why real-time matters"
→ You're reading it! Keep going.

### Path 2: "I need to understand the system"
1. **[01-requirements.md](./01-requirements.md)** — What must work, compare Reverb vs Pusher
2. **[02-architecture.md](./02-architecture.md)** — How channels, events, listeners connect
3. **[03-implementation-plan.md](./03-implementation-plan.md)** — Step-by-step to build it

### Path 3: "I just want to implement it"
→ Jump to [03-implementation-plan.md](./03-implementation-plan.md)

---

## ✅ Success Look & Feel

### For User
```
Task assigned to me by Alice:

OLD (5s polling):
  Bell icon: [🔔]                        [tick... tick... tick... DING]
  Badge appears with "1" after 5s        ← feels slow

NEW (real-time):
  Bell icon: [🔔 1]  ← instant          [DING right now]
  Notification dropdown already updated  ← feels snappy
```

### For Developer
```php
// No changes in UseCase/Application layer
$this->notificationService->notifyOne(
    event: 'task.assigned',
    tenantId: $tenantId,
    userId: $assigneeId,
    context: [...]
);

// ✅ Real-time happens automatically behind the scenes
// ✅ User sees notification on their bell instantly
```

---

## 🚫 Out of Scope (We DON'T do this first)

- **Browser push notifications** (separate integration)
- **Notification toast/popup** (Phase 2)
- **Email notifications** (handled by Mail Service)
- **Offline queueing** (Echo handles auto-reconnect)

---

## 📚 Related Docs

- [Notification System](../notification-system/readme.md) — How notifications are created (we enhance this with real-time)
- [Audit System](../audit-system/readme.md) — Activity logs (different from notifications)

---

## ❓ Common Questions

**Q: Do we have to use Reverb or can we use Pusher?**  
A: Either works! See [01-requirements.md](./01-requirements.md) for comparison. Reverb for dev, choice later for production.

**Q: Won't WebSocket connections drain our server?**  
A: Reverb is built for this. Thousands of concurrent connections. See [02-architecture.md](./02-architecture.md) for scaling notes.

**Q: What if WebSocket dies?**  
A: Echo auto-reconnects. During downtime, polling fallback keeps it working. See [03-implementation-plan.md](./03-implementation-plan.md) verification.

**Q: Do I have to learn WebSocket to implement this?**  
A: No! Just know: event fired → channel → listener called. Laravel Echo handles the rest.

**Q: Is this the same infrastructure for chat?**  
A: Yes! Same channels, same Echo. Chat will add `conversation` channels and `presence` awareness.
