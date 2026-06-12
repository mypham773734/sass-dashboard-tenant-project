# Flow 2: CRUD qua Clean Architecture (4 layers)

**Phạm vi:** Một request "store/update" đi qua đủ 4 layer (Http → Application → Domain → Infrastructure), minh họa bằng feature **Task** (`POST /admin/task`, `PUT /admin/task/{id}`).

---

## 1. Sơ đồ tổng quát

```
HTTP POST /admin/task
  │
  ▼
[auth] [SetDefaultTenant] [chooseTenant]   (xem 01-request-lifecycle.md)
  │
  ▼
TaskController::store(StoreTaskRequest $request)
  │  ├─ Gate::authorize('create', [Task::class, $tenantId])   (Policy)
  │  ├─ $dto = CreateTaskDTO::fromArray($request->validated())  // snake_case → camelCase
  │  ├─ $tenantId  = tenantContext()->getId()
  │  └─ $createdBy = authContext()->getId()
  │
  ▼
CreateTaskUseCase::execute($dto, $tenantId, $createdBy)
  │  ├─ new TaskEntity(...)                       // Domain entity, pure PHP
  │  ├─ $taskRepository->create($entity)          // Domain interface
  │  └─ notifySystem($task, $tenantId)            // side-effects (xem 03-cross-cutting-flows.md)
  │       ├─ notificationService->notifyOne('task.assigned', ...)
  │       └─ audit->log('task.created', ...)
  │
  ▼
EloquentTaskRepository::create(TaskEntity $entity): TaskEntity
  │  ├─ Task::create([...])     // Eloquent model, snake_case columns
  │  └─ toEntity($model)        // map về lại Domain Entity (camelCase)
  │
  ▼
Controller nhận về TaskEntity
  └─ redirect()->route('task.index')->with('success', 'Task created!')
```

---

## 2. Chi tiết từng layer

### 2.1 Http Layer — Controller

File: [`app/Http/Controllers/Admin/TaskController.php`](../../app/Http/Controllers/Admin/TaskController.php)

```php
public function index()
{
    try {
        $tenantId = tenantContext()->getId();
        $this->authorize('viewAny', [Task::class, $tenantId]);   // ← Policy check

        $tasks = $this->getTasksUseCase->execute($tenantId);

        return view('admin.pages.task.index', compact('tasks', 'tenantId'));
    } catch (AuthorizationException | HttpException $e) {
        throw $e;                                                  // ← để Laravel xử lý 403/404
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to load tasks.');
    }
}
```

Khác biệt nhỏ so với `PATTERNS.md`: `AuthorizationException | HttpException` được **re-throw** (không catch như lỗi nghiệp vụ) để Laravel tự render trang 403/404 chuẩn — đây là pattern hợp lý khi có Policy.

`store()`/`update()` theo đúng pattern 3 catch trong `PATTERNS.md`:
```php
try {
    $dto = CreateTaskDTO::fromArray($request->validated());
    $task = $this->createTaskUseCase->execute($dto, tenantContext()->getId(), authContext()->getId());
    return redirect()->route('task.index')->with('success', 'Task created!');
} catch (\DomainException $e) {
    return back()->with('error', $e->getMessage())->withInput();
} catch (\Exception $e) {
    Log::error($e->getMessage());
    return back()->with('error', 'Something went wrong.')->withInput();
}
```

### 2.2 Application Layer — DTO + UseCase

**DTO** (`CreateTaskDTO`) — convert snake_case (form) → camelCase (domain):
```php
CreateTaskDTO::fromArray($request->validated())
// $request->validated() = ['project_id' => 1, 'assignee_id' => 5, 'due_date' => '2026-07-01', ...]
// → $dto->projectId, $dto->assigneeId, $dto->dueDate
```

**UseCase** (`CreateTaskUseCase`) — orchestration, KHÔNG có Eloquent:
```php
public function execute(CreateTaskDTO $dto, int $tenantId, int $createdBy): TaskEntity
{
    $entity = new TaskEntity(id: null, tenantId: $tenantId, createdBy: $createdBy, ...);
    $task = $this->taskRepository->create($entity);
    $this->notifySystem($task, $tenantId);
    return $task;
}
```

`UpdateTaskUseCase` có thêm business logic đáng chú ý — **auto-set `completedAt`** khi status chuyển sang `done`:
```php
private function calculateCompletedAt(TaskEntity $existing, UpdateTaskDTO $dto)
{
    if ($dto->status === 'done' && $existing->status !== 'done') {
        return new \DateTime();          // chuyển sang done → set completedAt = now
    } elseif ($dto->status !== 'done') {
        return null;                     // chuyển ra khỏi done → reset completedAt
    }
    return $existing->completedAt;       // giữ nguyên
}
```

→ Đây là ví dụ điển hình cho "business rule thuộc về UseCase, không phải Controller hay Repository".

### 2.3 Domain Layer — Entity + Repository Interface

```
app/Domain/Task/Entities/TaskEntity.php          ← pure PHP, readonly properties, camelCase
app/Domain/Task/Repositories/TaskRepositoryInterface.php
```

`TaskRepositoryInterface` định nghĩa contract: `findById(int $id, int $tenantId)`, `create()`, `update()`, `getAllByTenant()`, `delete()` — UseCase chỉ biết interface này.

### 2.4 Infrastructure Layer — Eloquent Repository

```
app/Infrastructure/Persistence/Repositories/EloquentTaskRepository.php
```

Trách nhiệm:
1. Query Eloquent model `Task` (snake_case columns: `project_id`, `assignee_id`, `due_date`, `completed_at`)
2. `toEntity($model)` → map sang `TaskEntity` (camelCase)
3. `toArray($entity)` → map ngược lại khi `create()`/`update()`

Binding: `AppServiceProvider::boot()`:
```php
$this->app->bind(TaskRepositoryInterface::class, EloquentTaskRepository::class);
```

---

## 3. So sánh với Tenant feature (có thêm bước "Tenant Switch")

`TenantController` có thêm action đặc biệt không thuộc CRUD chuẩn:

```php
public function switchTenant(int $tenantId)
{
    try {
        $userId = authContext()->getId();
        $this->changeTenantSelectedUseCase->execute($userId, $tenantId);  // validate membership

        tenantContext()->setId($tenantId);   // ← ghi session NGAY TẠI CONTROLLER

        return redirect()->route('dashboard')->with('success', 'Workspace switched.');
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage());
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to switch workspace.');
    }
}
```

`DeleteTenantUseCase` cũng có thứ tự thao tác đáng chú ý: **dispatch notification TRƯỚC khi `detachAllUsers()`** — nếu đảo ngược thứ tự, `TenantNotificationHandler` sẽ không tìm được admin nào để gửi (vì đã bị detach khỏi tenant).

---

## 4. Checklist khi đọc 1 feature mới

Khi cần hiểu nhanh 1 feature (VD: Project, User, TenantSetting), đọc theo thứ tự:

1. `app/Http/Controllers/Admin/{Feature}Controller.php` — entry point, validation, DTO mapping
2. `app/Application/{Feature}/UseCases/*.php` — business rules, side-effects (`notifySystem`, `audit->log`, `mailService->dispatch`)
3. `app/Domain/{Feature}/Entities/*Entity.php` — shape dữ liệu (camelCase)
4. `app/Infrastructure/Persistence/Repositories/Eloquent{Feature}Repository.php` — mapping DB ↔ Entity

→ Side-effects (Audit/Mail/Notification) được phân tích chi tiết ở [03-cross-cutting-flows.md](./03-cross-cutting-flows.md).
