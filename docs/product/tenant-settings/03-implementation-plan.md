# Tenant Settings — Implementation Plan

**Status:** Ready to code  
**Effort:** ~5 hours (5 phases, 1 hour each)  
**Complexity:** Medium

---

## 📋 Overview

5-phase implementation with clear deliverables each phase.

| Phase | Focus | Hours | Files | Deliverable |
|-------|-------|-------|-------|-------------|
| 1 | DB + Domain | 1 | 4 | Settings stored in DB, accessible via Domain |
| 2 | Service + App | 1 | 5 | UseCase pattern, dependency injection |
| 3 | UI + Forms | 1.5 | 8-10 | Admin Settings tab with forms |
| 4 | Integration | 1 | 6 | Use in NotificationService, CleanupCommand |
| 5 | Tests | 1 | 3-5 | Unit + Feature tests |
| **Total** | | **5.5** | **~26** | **Production ready** |

---

## Phase 1: Database + Domain (1 hour)

### Files to Create
```
database/migrations/2026_06_10_xxxxxx_create_tenant_settings_table.php
app/Domain/TenantSetting/Entities/TenantSettingEntity.php
app/Domain/TenantSetting/Repositories/TenantSettingRepositoryInterface.php
app/Models/TenantSetting.php
```

### Step 1.1: Create Migration

```php
// database/migrations/2026_06_10_xxxxxx_create_tenant_settings_table.php

Schema::create('tenant_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('key');                    // e.g., 'email.task_assigned'
    $table->json('value')->nullable();        // stores boolean/int/string as JSON
    $table->timestamps();
    
    $table->unique(['tenant_id', 'key']);     // one row per tenant + key
});
```

**Run:** `php artisan migrate`

### Step 1.2: Create Domain Entity

```php
// app/Domain/TenantSetting/Entities/TenantSettingEntity.php

namespace App\Domain\TenantSetting\Entities;

class TenantSettingEntity {
    public function __construct(
        public readonly ?int   $id,
        public readonly int    $tenantId,
        public readonly string $key,
        public readonly mixed  $value,
    ) {}
}
```

### Step 1.3: Create Repository Interface

```php
// app/Domain/TenantSetting/Repositories/TenantSettingRepositoryInterface.php

namespace App\Domain\TenantSetting\Repositories;

interface TenantSettingRepositoryInterface {
    public function getAllForTenant(int $tenantId): array;
    public function upsertMany(int $tenantId, array $pairs): void;
}
```

### Step 1.4: Create Eloquent Model

```php
// app/Models/TenantSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model {
    protected $fillable = ['tenant_id', 'key', 'value'];
    protected $casts = ['value' => 'json'];
    // No TenantScope — settings always queried explicitly
}
```

**Note:** `TenantSettingDefaults` already exists from earlier work.

**Verification:** Migration runs, `tenant_settings` table created with columns: id, tenant_id, key, value, timestamps, unique(tenant_id, key)

---

## Phase 2: Repository + Application (1 hour)

### Files to Create
```
app/Infrastructure/Persistence/Repositories/EloquentTenantSettingRepository.php
app/Application/Tenant/UseCases/UpdateTenantSettingUseCase.php
app/Application/Tenant/UseCases/GetTenantSettingsUseCase.php
(Update: app/Shared/helpers.php, app/Providers/AppServiceProvider.php)
```

### Step 2.1: Implement Repository

```php
// app/Infrastructure/Persistence/Repositories/EloquentTenantSettingRepository.php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Cache;

class EloquentTenantSettingRepository implements TenantSettingRepositoryInterface {
    private const int TTL = 600;

    public function getAllForTenant(int $tenantId): array {
        $cacheTag = "tenant:{$tenantId}:settings";
        $cacheKey = "tenant_settings:{$tenantId}";

        return Cache::tags([$cacheTag])->remember($cacheKey, self::TTL, function () use ($tenantId) {
            $stored = TenantSetting::where('tenant_id', $tenantId)
                ->pluck('value', 'key')
                ->toArray();
            return array_replace_recursive(
                TenantSettingDefaults::DEFAULTS,
                $stored
            );
        });
    }

    public function upsertMany(int $tenantId, array $pairs): void {
        foreach ($pairs as $key => $value) {
            TenantSetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => $value],
            );
        }
        Cache::tags(["tenant:{$tenantId}:settings"])->flush();
    }
}
```

### Step 2.2: Create UseCases

```php
// app/Application/Tenant/UseCases/UpdateTenantSettingUseCase.php

namespace App\Application\Tenant\UseCases;

use App\Application\Tenant\DTOs\UpdateTenantSettingDTO;
use App\Domain\Tenant\TenantSettingDefaults;
use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;

class UpdateTenantSettingUseCase {
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settingRepository,
    ) {}

    public function execute(int $tenantId, UpdateTenantSettingDTO $dto): void {
        if (!array_key_exists($dto->section, TenantSettingDefaults::DEFAULTS)) {
            throw new \DomainException("Unknown settings section: {$dto->section}");
        }

        $pairs = [];
        foreach ($dto->values as $key => $value) {
            $pairs["{$dto->section}.{$key}"] = $value;
        }

        $this->settingRepository->upsertMany($tenantId, $pairs);
    }
}

// app/Application/Tenant/UseCases/GetTenantSettingsUseCase.php

namespace App\Application\Tenant\UseCases;

use App\Domain\Tenant\TenantSettingDefaults;
use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;

class GetTenantSettingsUseCase {
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settingRepository,
    ) {}

    public function execute(int $tenantId): array {
        return $this->settingRepository->getAllForTenant($tenantId);
    }
}
```

### Step 2.3: Register in Container

```php
// app/Providers/AppServiceProvider.php

$this->app->bind(
    TenantSettingRepositoryInterface::class,
    EloquentTenantSettingRepository::class,
);
```

### Step 2.4: Add Global Helper

```php
// app/Shared/helpers.php

use App\Domain\Tenant\TenantSettingDefaults;
use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;

if (!function_exists('tenantSetting')) {
    function tenantSetting(string $key, mixed $default = null, ?int $tenantId = null): mixed {
        $tenantId = $tenantId ?? tenantContext()->getId();
        $stored = app(TenantSettingRepositoryInterface::class)->getAllForTenant($tenantId);

        return $stored[$key] ?? data_get(TenantSettingDefaults::DEFAULTS, $key, $default);
    }
}
```

**Verification:** 
```php
tenantSetting('email.task_assigned', true, 1); // Returns stored value or default
```

---

## Phase 3: UI Layer (1.5 hours)

### Files to Create
```
app/Http/Controllers/Admin/TenantSettingController.php
app/Http/Requests/Tenant/UpdateTenantSettingRequest.php
resources/views/admin/layouts/tenant-settings.blade.php
resources/views/admin/components/settings-sidebar.blade.php
resources/views/admin/pages/tenant/settings/email.blade.php
resources/views/admin/pages/tenant/settings/notifications.blade.php
resources/views/admin/pages/tenant/settings/localization.blade.php
resources/views/admin/pages/tenant/settings/members.blade.php
resources/views/admin/components/setting-toggle.blade.php
(Update: routes/web.php, resources/views/admin/pages/tenant/index.blade.php)
```

### Step 3.1: Create Controller

Settings is split into a **submenu of sections**, each with its own URL
segment (`{section}` = `email`, `notifications`, `localization`, `members`).
One controller serves all sections.

```php
// app/Http/Controllers/Admin/TenantSettingController.php

namespace App\Http\Controllers\Admin;

use App\Application\Tenant\DTOs\UpdateTenantSettingDTO;
use App\Application\Tenant\UseCases\GetTenantSettingsUseCase;
use App\Application\Tenant\UseCases\UpdateTenantSettingUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantSettingRequest;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TenantSettingController extends Controller {

    private const SECTIONS = ['email', 'notifications', 'localization', 'members'];

    public function __construct(
        private readonly GetTenantSettingsUseCase $getUseCase,
        private readonly UpdateTenantSettingUseCase $updateUseCase,
    ) {}

    public function index(int $tenantId, string $section = 'email') {
        if (!in_array($section, self::SECTIONS, true)) {
            abort(404);
        }

        try {
            $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
            $this->authorize('edit', $tenant);

            $settings = $this->getUseCase->execute($tenantId);

            return view("admin.pages.tenant.settings.{$section}", [
                'tenantId' => $tenantId,
                'tenant'   => $tenant,
                'section'  => $section,
                'settings' => $settings,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load settings.');
        }
    }

    public function update(int $tenantId, string $section, UpdateTenantSettingRequest $request) {
        if (!in_array($section, self::SECTIONS, true)) {
            abort(404);
        }

        try {
            $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
            $this->authorize('edit', $tenant);

            $dto = UpdateTenantSettingDTO::fromArray($section, $request->validated());
            $this->updateUseCase->execute($tenantId, $dto);

            return redirect()
                ->route('tenant.settings.index', [$tenantId, $section])
                ->with('success', 'Settings updated successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }
}
```

### Step 3.2: Create Request Validation

```php
// app/Http/Requests/Tenant/UpdateTenantSettingRequest.php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSettingRequest extends FormRequest {
    
    public function authorize(): bool {
        return $this->user()->hasPermissionInTenant('tenant:edit', (int) $this->route('tenantId'));
    }
    
    public function rules(): array {
        return match ($this->route('section')) {
            'email' => [
                'email' => ['required', 'array'],
                'email.task_assigned'         => ['required', 'boolean'],
                'email.task_status_changed'   => ['required', 'boolean'],
                'email.tenant_member_added'   => ['required', 'boolean'],
                'email.tenant_member_removed' => ['required', 'boolean'],
                'email.tenant_role_changed'   => ['required', 'boolean'],
            ],
            'notifications' => [
                'notifications' => ['required', 'array'],
                'notifications.retention_days' => ['required', 'integer', 'in:7,14,30,60,90'],
            ],
            'localization' => [
                'localization' => ['required', 'array'],
                'localization.timezone'    => ['required', 'timezone'],
                'localization.locale'      => ['required', 'string', 'in:en,vi'],
                'localization.date_format' => ['required', 'string', 'in:d/m/Y,Y-m-d,m/d/Y'],
            ],
            'members' => [
                'members' => ['required', 'array'],
                'members.default_role' => ['required', 'string', 'in:member,manager,guest'],
            ],
            default => [],
        };
    }
}
```

### Step 3.3: Create Views

Each section is its **own page** at `/admin/tenant/{id}/settings/{section}`.
A shared layout renders the submenu sidebar; each section view fills the
content slot.

```blade
{{-- resources/views/admin/layouts/tenant-settings.blade.php --}}

@extends('admin.layouts.app')

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-900">Settings</h3>
        <p class="text-gray-500 mt-1">{{ $tenant->name }}</p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <x-admin.settings-sidebar :tenant-id="$tenantId" :active="$section" />

        <div class="flex-1 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            @yield('settings-content')
        </div>
    </div>
</main>
@endsection
```

```blade
{{-- resources/views/admin/components/settings-sidebar.blade.php --}}
@props(['tenantId', 'active'])

@php
    $items = [
        'email'         => ['label' => 'Email',         'icon' => 'fa-envelope'],
        'notifications' => ['label' => 'Notifications', 'icon' => 'fa-bell'],
        'localization'  => ['label' => 'Localization',  'icon' => 'fa-globe'],
        'members'       => ['label' => 'Members',       'icon' => 'fa-users'],
    ];
@endphp

<nav class="md:w-56 shrink-0 space-y-1">
    @foreach ($items as $key => $item)
        <a href="{{ route('tenant.settings.index', [$tenantId, $key]) }}"
           @class([
               'flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium transition-all',
               'bg-indigo-50 text-indigo-600' => $active === $key,
               'text-gray-600 hover:bg-gray-50' => $active !== $key,
           ])>
            <i class="fas {{ $item['icon'] }} w-4"></i>
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
```

```blade
{{-- resources/views/admin/pages/tenant/settings/email.blade.php --}}

@extends('admin.layouts.tenant-settings')

@section('settings-content')
<h4 class="text-lg font-semibold text-gray-900 mb-1">📧 Email Notifications</h4>
<p class="text-sm text-gray-500 mb-4">Choose which events send an email notification.</p>

<form method="POST" action="{{ route('tenant.settings.update', [$tenantId, 'email']) }}">
    @csrf
    @foreach ($settings['email'] as $key => $value)
        <x-admin.setting-toggle
            name="email[{{ $key }}]"
            label="{{ ucwords(str_replace('_', ' ', $key)) }}"
            :checked="$value" />
    @endforeach

    <div class="pt-4 mt-4 border-t border-gray-100">
        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
            Save changes
        </button>
    </div>
</form>
@endsection
```

`notifications.blade.php`, `localization.blade.php`, `members.blade.php` follow
the same pattern — `@extends('admin.layouts.tenant-settings')`, post to
`route('tenant.settings.update', [$tenantId, '<section>'])`.

### Step 3.4: Add Routes

```php
// routes/web.php — add inside existing admin auth group

use App\Http\Controllers\Admin\TenantSettingController;

Route::prefix('tenant/{tenantId}/settings')
    ->name('tenant.settings.')
    ->group(function () {
        Route::get('/{section?}', [TenantSettingController::class, 'index'])->name('index');
        Route::post('/{section}', [TenantSettingController::class, 'update'])->name('update');
    });
```

### Step 3.5: Add Settings Link in Tenant Index

In `resources/views/admin/pages/tenant/index.blade.php`, add a Settings icon button next to Edit/Delete actions:

```blade
<a href="{{ route('tenant.settings.index', $tenant->id) }}" 
   class="p-2 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition h-fit">
    <i class="fas fa-cog"></i>
</a>
```

**Verification:**
- Navigate to `/admin/tenant/1/settings` → redirects to Email section
- Sidebar shows 4 submenu items, active one highlighted
- Clicking submenu items navigates and shows correct section form
- Toggling a setting and saving persists to `tenant_settings` table

---

## Phase 4: Integration (Follow-up, not in this plan)

### Files to Modify
```
app/Infrastructure/Notifications/NotificationService.php
app/Console/Commands/CleanupOldNotificationsCommand.php
resources/views/ (any date/time displays)
routes/web.php (add settings routes)
```

### Step 4.1: Update NotificationService

```php
// app/Infrastructure/Notifications/NotificationService.php

public function notifyOne(string $event, int $tenantId, int $userId, array $context = []): void {
    // Check if email enabled for this event
    $emailKey = "email.{$event}";
    $emailEnabled = tenantSetting($emailKey, true, $tenantId);
    
    if (!$emailEnabled) {
        // Only in-app, skip email
        return;
    }
    
    // Original dispatch logic...
    WriteNotificationJob::dispatch(...)->onQueue(...);
}
```

### Step 4.2: Update CleanupCommand

```php
// app/Console/Commands/CleanupOldNotificationsCommand.php

public function handle(): int {
    $days = (int) $this->option('days');
    $before = now()->subDays($days);
    
    $tenants = Tenant::withoutGlobalScopes()->pluck('id');
    
    foreach ($tenants as $tenantId) {
        // Read retention setting for this tenant
        $retentionDays = tenantSetting('notifications.retention_days', 30, $tenantId);
        $tenantBefore = now()->subDays($retentionDays);
        
        $deleted = $this->notificationRepository->deleteOlderThan($tenantId, $tenantBefore);
        $this->info("Deleted {$deleted} notifications from tenant {$tenantId}");
    }
    
    return self::SUCCESS;
}
```

### Step 4.3: Update Views for Timezone

```blade
@php
    $timezone = tenantSetting('localization.timezone', 'UTC');
    $createdAt = $notification->created_at->setTimezone($timezone);
@endphp

<p>Created: {{ $createdAt->format('d/m/Y H:i') }}</p>
```

### Step 4.4: Add Routes

```php
// routes/web.php

Route::middleware(['auth', 'chooseTenant'])->group(function () {
    Route::get('/admin/tenant/{id}/settings', [TenantSettingController::class, 'index'])
        ->name('tenant.settings.index');
    Route::post('/admin/tenant/{id}/settings', [TenantSettingController::class, 'update'])
        ->name('tenant.settings.update');
});
```

**Verification:**
- Email not sent when disabled
- Cleanup uses tenant's retention setting
- Dates show in correct timezone

---

## Phase 4+: Integration & Testing (Follow-up, not in this plan)

These phases are deferred to follow-up work:
- **Phase 4:** Wire `tenantSetting()` into NotificationService, CleanupCommand, views, and AttachUserUseCase.
- **Phase 5:** Unit + feature tests for repository, use cases, and controller.

---

## ✅ Completion Checklist

### Phase 1
- [ ] Migration creates `tenant_settings` table (id, tenant_id, key, value, unique(tenant_id, key), timestamps)
- [ ] `TenantSettingEntity` created
- [ ] `TenantSettingRepositoryInterface` defined
- [ ] `TenantSetting` Eloquent model created

### Phase 2
- [ ] `EloquentTenantSettingRepository` implementation works (getAllForTenant + caching, upsertMany + cache flush)
- [ ] `GetTenantSettingsUseCase` created
- [ ] `UpdateTenantSettingUseCase` created
- [ ] Global `tenantSetting()` helper working
- [ ] Container binding registered

### Phase 3
- [ ] `TenantSettingController` created with try/catch
- [ ] `UpdateTenantSettingRequest` validation works
- [ ] Settings layout + sidebar + toggle components render
- [ ] 4 settings section views (email, notifications, localization, members) render
- [ ] Routes registered
- [ ] Settings link added to tenant index
- [ ] Toggling a setting persists to `tenant_settings` table
- [ ] Non-admin users get 403

### Phase 4+ (Follow-up)
- [ ] NotificationService uses email settings
- [ ] CleanupCommand uses retention setting
- [ ] Views apply timezone setting
- [ ] Tests pass

---

## 🚀 Next Steps

1. Start Phase 1 — Create migration and domain
2. Run `php artisan migrate` to create column
3. Proceed through phases sequentially
4. Test each phase before moving to next

---

## References

**See also:**
- [01-requirements.md](./01-requirements.md) — What needs to be configurable
- [02-architecture.md](./02-architecture.md) — How it's structured
