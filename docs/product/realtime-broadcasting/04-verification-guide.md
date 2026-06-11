# Real-Time Broadcasting — Verification Guide

**Status:** Implementation complete, ready for manual testing  
**Level:** QA + Developer  
**Purpose:** Step-by-step manual tests to verify real-time delivery works

---

## 🚀 Setup: Start Development Stack

Open 4 terminals:

**Terminal 1: Start Reverb WebSocket Server**
```bash
php artisan reverb:start
```
Expected output:
```
Starting Reverb server...
Server listening on ws://127.0.0.1:8080
```

**Terminal 2: Start Queue Worker**
```bash
php artisan queue:listen
```
Watch for `Queued: WriteNotificationJob` messages when jobs dispatch.

**Terminal 3: Start Dev Server**
```bash
php artisan serve
```
Opens http://127.0.0.1:8000

**Terminal 4: Watch Logs (Optional)**
```bash
php artisan pail
```
Shows real-time application logs.

---

## ✅ Test 1: Single User, Single Browser (Basic Real-Time)

### Setup
1. Open browser: http://127.0.0.1:8000
2. Login as User 5 (or any user)
3. Verify notification bell loads (badge may show 0 or cached count)

### Test Steps
1. **Verify WebSocket Connected**
   - Open browser DevTools → Console
   - Type: `window.Echo` → Should return Echo object
   - Type: `window.Echo.connector.socket` → Should show WebSocket connection

2. **Create Test Notification (via Tinker)**
   ```bash
   # In terminal (or new terminal):
   php artisan tinker
   
   >>> use App\Infrastructure\Notifications\Jobs\WriteNotificationJob;
   >>> use App\Application\Notification\DTOs\NotificationDTO;
   >>> use Carbon\Carbon;
   
   >>> $dto = new NotificationDTO(
       event: 'task.assigned',
       title: 'Test: Task assigned to you',
       body: null,
       url: '/dashboard',
       data: []
   );
   
   >>> WriteNotificationJob::dispatch($dto, 5, 3);  // userId=5, tenantId=3
   ```

3. **Observe Bell Update**
   - **Expected:** Bell badge updates instantly (< 100ms) with "1"
   - **NOT Expected:** Page reload, network request, delay

4. **Verify in Logs (Terminal 4)**
   - Should see queue processing message
   - Should see broadcast event fired

### Success Criteria
- ✅ Bell badge appears instantly (no 5s polling delay)
- ✅ No page refresh
- ✅ Unread count accurate: 1

---

## ✅ Test 2: Multi-User, Two Browsers (Isolation)

### Setup
- Browser 1: User 5, Tenant 3
- Browser 2: User 7, Tenant 3 (same tenant, different user)
- Position side-by-side for observation

### Test Steps
1. **Create notification for User 5**
   ```bash
   php artisan tinker
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```
   
   - **Browser 1 (User 5):** Bell updates ✅
   - **Browser 2 (User 7):** NO UPDATE ❌ (should not see it)

2. **Create notification for User 7**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 7, 3);
   ```
   
   - **Browser 1 (User 5):** NO UPDATE ✅
   - **Browser 2 (User 7):** Bell updates ✅

3. **Create notification for both users**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   >>> WriteNotificationJob::dispatch($dto, 7, 3);
   ```
   
   - **Browser 1 & 2:** Both bells update instantly ✅

### Success Criteria
- ✅ User 5 only sees User 5 notifications
- ✅ User 7 only sees User 7 notifications
- ✅ No cross-contamination

---

## ✅ Test 3: Tenant Switching (Scoping)

### Setup
- Browser: User 5, member of Tenant 3 AND Tenant 7
- Must have access to both tenants via dashboard

### Test Steps
1. **In Tenant 3**
   - Create notification for Tenant 3, User 5:
     ```bash
     >>> WriteNotificationJob::dispatch($dto, 5, 3);
     ```
   - **Browser:** Bell updates ✅
   - **Count:** Unread shows "1"

2. **Switch to Tenant 7 (click tenant dropdown)**
   - Page reloads/component remounts
   - Bell resets (now showing Tenant 7 notifications)
   - **Count:** May show 0 (if no notifications in T7)

3. **Create notification for OLD Tenant 3**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 5, 3);  // Tenant 3, not current
   ```
   - **Browser:** Bell does NOT update ❌ (correct!)
   - User is in Tenant 7, should only see T7 notifications

4. **Create notification for CURRENT Tenant 7**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 5, 7);  // Tenant 7, current
   ```
   - **Browser:** Bell updates ✅
   - **Count:** Increments correctly

5. **Switch back to Tenant 3**
   - Bell shows previous Tenant 3 notifications again
   - Count accurate for Tenant 3

### Success Criteria
- ✅ Notifications scoped per tenant
- ✅ Tenant switch = channel unsubscribe/resubscribe
- ✅ Cannot see notifications from inactive tenant
- ✅ Switching back restores correct notifications

---

## ✅ Test 4: Two Devices, Same User (Multi-Device)

### Setup
- Device 1 (Browser): User 5, Tenant 3
- Device 2 (Mobile/Second Browser): Same User 5, Tenant 3
- Logged in as same user

### Test Steps
1. **Create notification**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```

2. **Both devices simultaneously:**
   - Device 1: Bell updates ✅
   - Device 2: Bell updates ✅
   - Both show same unread count: "1" ✅

### Success Criteria
- ✅ Broadcast sent to channel (not user), so all devices subscribed receive it
- ✅ No race conditions

---

## ✅ Test 5: Stress Test (High Volume)

### Setup
- Browser: User 5, Tenant 3
- Rapid-fire notifications

### Test Steps
1. **Create 10 notifications rapidly**
   ```bash
   php artisan tinker
   >>> for ($i = 1; $i <= 10; $i++) {
       WriteNotificationJob::dispatch($dto, 5, 3);
   }
   ```

2. **Observe:**
   - Bell badge updates from 0 → 10 progressively
   - UI doesn't freeze/hang
   - Dropdown shows all 10 notifications
   - CPU usage reasonable (< 50%)

3. **Create 100 notifications**
   ```bash
   >>> for ($i = 1; $i <= 100; $i++) {
       WriteNotificationJob::dispatch($dto, 5, 3);
   }
   ```

4. **Observe:**
   - System handles load gracefully
   - Reverb logs no errors
   - Queue worker keeps up
   - Badge shows 100 unread ✅

### Success Criteria
- ✅ Reverb handles 100+ concurrent messages
- ✅ No crashes or hangs
- ✅ Queue worker processes all
- ✅ UI responsive

---

## ✅ Test 6: Reverb Downtime (Fallback)

### Setup
- Browser: Reverb running, notifications working
- Queue worker running

### Test Steps
1. **Verify baseline: 1 notification sent**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```
   - Browser: Bell updates instantly ✅

2. **Stop Reverb server**
   - In Terminal 1: Press `Ctrl+C`
   - Reverb stops

3. **Create new notification**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```
   - **Browser:** Bell does NOT update (WebSocket dead)
   - **Notification saved to DB:** ✅ (check in tinker)

4. **Restart Reverb**
   - Terminal 1: `php artisan reverb:start`
   - Reverb comes back online

5. **Browser reloads page**
   - Bell shows all unread notifications from downtime ✅
   - Count accurate

6. **Create new notification (after restart)**
   ```bash
   >>> WriteNotificationJob::dispatch($dto, 5, 3);
   ```
   - Browser: Bell updates instantly ✅ (WebSocket working again)

### Success Criteria
- ✅ Notifications persist in DB during downtime
- ✅ No data loss
- ✅ Real-time resumes after Reverb restart
- ✅ Manual page reload recovers state

---

## ✅ Test 7: Channel Authorization (Security)

### Setup
- Browser: User 5, Tenant 3
- Browser DevTools Console open

### Test Steps
1. **Try to subscribe to unauthorized channel (in Console)**
   ```javascript
   // Hacker tries to listen to User 7's notifications
   window.Echo.private(`tenant.3.user.7`).listen('notification-created', (data) => {
       console.log('Hacked!', data);
   });
   ```
   
   - **Expected:** Subscription REJECTED
   - Check Network tab → `/broadcasting/auth` returns 401/403
   - No listener established

2. **Try to subscribe to different tenant (in Console)**
   ```javascript
   // Try to access Tenant 5 (user not member)
   window.Echo.private(`tenant.5.user.5`).listen('notification-created', (data) => {
       console.log('Hacked!', data);
   });
   ```
   
   - **Expected:** Subscription REJECTED
   - Authorization fails on backend

3. **Verify authorized channel works**
   ```javascript
   // User 5's legitimate channel
   window.Echo.private(`tenant.3.user.5`).listen('notification-created', (data) => {
       console.log('Authorized:', data);
   });
   ```
   
   - **Expected:** Subscription ACCEPTED
   - Listener works

### Success Criteria
- ✅ Cannot subscribe to other users' channels
- ✅ Cannot subscribe to channels outside own tenants
- ✅ Authorization enforced on backend
- ✅ Only authorized channels accessible

---

## ✅ Test 8: Database Consistency

### Setup
- Notifications created and broadcast delivered
- Browser showing notification bell

### Test Steps
1. **Verify notification in DB**
   ```bash
   php artisan tinker
   >>> use App\Models\Notification;
   >>> Notification::where('user_id', 5)->where('tenant_id', 3)->latest()->first();
   ```
   
   - Should show: `id`, `user_id=5`, `tenant_id=3`, `title`, `is_read=false`

2. **Mark as read in UI**
   - Browser: Click notification → should mark as read

3. **Verify in DB**
   ```bash
   >>> Notification::where('user_id', 5)->where('tenant_id', 3)->latest()->first()->is_read;
   // Should be true (1)
   ```

4. **Mark all as read**
   - Browser: Click "Mark All" button

5. **Verify all marked in DB**
   ```bash
   >>> Notification::where('user_id', 5)->where('tenant_id', 3)->where('is_read', false)->count();
   // Should be 0
   ```

### Success Criteria
- ✅ DB state matches UI state
- ✅ Marks persist
- ✅ No stale data

---

## 🧪 Automated Tests (Already Passing)

Run to verify code quality:

```bash
# Broadcast event tests
php artisan test tests/Feature/Notifications/BroadcastNotificationTest.php

# All notification tests
php artisan test tests/Feature/Notifications/

# All tests
composer run test
```

Expected: **All tests PASS**

---

## 📊 Test Results Template

Use this to document your results:

```
┌────────────────────────────────────────┐
│ Real-Time Broadcasting Test Results    │
├────────────────────────────────────────┤
│ Date: ____________________             │
│ Tester: ____________________           │
│ Environment: Dev / Staging / Prod      │
├────────────────────────────────────────┤
│ Test 1: Single User        ✅ / ❌    │
│ Test 2: Multi-User         ✅ / ❌    │
│ Test 3: Tenant Switching   ✅ / ❌    │
│ Test 4: Multi-Device       ✅ / ❌    │
│ Test 5: Stress Test        ✅ / ❌    │
│ Test 6: Downtime Fallback  ✅ / ❌    │
│ Test 7: Authorization      ✅ / ❌    │
│ Test 8: DB Consistency     ✅ / ❌    │
│ Automated Tests            ✅ / ❌    │
├────────────────────────────────────────┤
│ Overall: ✅ PASS / ❌ FAIL            │
└────────────────────────────────────────┘
```

---

## 🆘 Troubleshooting

### Issue: Bell doesn't update, no WebSocket error
**Check:**
1. Is Reverb running? → Terminal 1 `php artisan reverb:start`
2. Is Queue worker running? → Terminal 2 `php artisan queue:listen`
3. Is npm build fresh? → `npm run build`
4. Check browser console for errors

### Issue: WebSocket connection refused
**Check:**
1. Reverb started on correct port: `REVERB_PORT=8080` in `.env`
2. Firewall blocks port 8080: open it or change port
3. Check `resources/js/echo.js` config matches `.env`

### Issue: Authorization always fails
**Check:**
1. User authenticated: `auth()->id()` returns user ID
2. User belongs to tenant: check `tenant_user` pivot table
3. Query works: `User::find(5)->tenants()->wherePivot('tenant_id', 3)->exists()` = true

### Issue: Notifications not appearing in bell after manual dispatch
**Check:**
1. Is notification saved to DB? → Check `notifications` table
2. Are you in correct tenant? → `tenantContext()->getId()`
3. Queue job executed? → Terminal 2 shows `Processed: WriteNotificationJob`

---

## ✅ Sign-Off: Ready for Production?

All tests pass → **YES, safe to deploy** ✅

Document issues found → **Create GitHub issue** and fix before deploy

---

**Next:** After verification, Phase 4 is complete. System is production-ready for real-time notifications!
