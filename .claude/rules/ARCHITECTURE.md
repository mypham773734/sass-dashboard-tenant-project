# Architecture Rules

**Reference:** Use when building new features or unsure about layer placement.

See [00-START-HERE.md](./00-START-HERE.md) for quick overview.

---

## Layer Structure & Dependencies

### 4-Layer Clean Architecture

```
HTTP/Controllers/Requests  ← Presentation
        ↓ (depends on)
   Application Layer       ← Orchestration (DTOs, UseCases)
        ↓ (depends on)
    Domain Layer          ← Pure business logic
        ↓ (depends on)
   Infrastructure         ← Laravel implementations
```

### Dependency Rules (STRICT)

| Layer | CAN depend on | CANNOT depend on |
|-------|---------------|-----------------|
| **Domain** | Nothing | Laravel, Eloquent, Application, Infrastructure |
| **Application** | Domain only | Laravel, Eloquent, Infrastructure |
| **Infrastructure** | Domain + Application | *(nothing)* |
| **Http** | Domain + Application | Infrastructure directly |

### Why These Rules?

- **Domain pure** → Can test without Laravel
- **Application stable** → Can reuse in APIs, CLI, events
- **Infrastructure flexible** → Can swap implementations (Eloquent → MongoDB)
- **Http thin** → Orchestration only, no logic

---

## Multi-Tenancy Architecture

### Global Scope (Automatic Filtering)

Every multi-tenant model has a Global Scope:

```php
// app/Models/Project.php
protected static function booted()
{
    static::addGlobalScope(new TenantScope);
}

// Result: Project::all() automatically filtered by current user's tenants
```

### Tenant Context (Session-Based)

```php
// In middleware/controller
tenantContext()->setId($tenantId);

// In use case
public function execute(CreateProjectDTO $dto, int $tenantId): Project
{
    // $tenantId passed explicitly, never read from session
}
```

### Service Container Bindings

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    $this->app->bind(
        TenantRepositoryInterface::class,
        EloquentTenantRepository::class,
    );
    
    $this->app->bind(
        ProjectRepositoryInterface::class,
        EloquentProjectRepository::class,
    );
}
```

---

## New Feature Checklist

When adding a feature like "Invoice Management":

### Step 1: Domain Layer (Pure PHP)
```php
// Define what we're managing
app/Domain/Invoice/Entities/InvoiceEntity.php
app/Domain/Invoice/Repositories/InvoiceRepositoryInterface.php

// Example:
class InvoiceEntity {
    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
        public readonly string $number,
        public readonly float $amount,
    ) {}
}
```

### Step 2: Application Layer
```php
// Define how to use it
app/Application/Invoice/DTOs/CreateInvoiceDTO.php
app/Application/Invoice/DTOs/UpdateInvoiceDTO.php
app/Application/Invoice/UseCases/CreateInvoiceUseCase.php
app/Application/Invoice/UseCases/UpdateInvoiceUseCase.php

// Example:
class CreateInvoiceUseCase {
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
    ) {}

    public function execute(CreateInvoiceDTO $dto, int $tenantId): InvoiceEntity
    {
        if ($dto->amount <= 0) {
            throw new \DomainException('Amount must be positive.');
        }
        return $this->repository->create($dto, $tenantId);
    }
}
```

### Step 3: Infrastructure Layer
```php
// Implement with Laravel/Eloquent
app/Models/Invoice.php (Eloquent model)
app/Infrastructure/Persistence/Repositories/EloquentInvoiceRepository.php

// Example:
class EloquentInvoiceRepository implements InvoiceRepositoryInterface {
    public function create(CreateInvoiceDTO $dto, int $tenantId): InvoiceEntity {
        $model = Invoice::create([
            'tenant_id' => $tenantId,
            'number' => $dto->number,
            'amount' => $dto->amount,
        ]);
        return $this->toEntity($model);
    }

    private function toEntity(Invoice $model): InvoiceEntity {
        return new InvoiceEntity(
            id: $model->id,
            tenantId: $model->tenant_id,
            number: $model->number,
            amount: $model->amount,
        );
    }
}
```

### Step 4: Http Layer
```php
// Create endpoints
app/Http/Controllers/InvoiceController.php
app/Http/Requests/StoreInvoiceRequest.php

// Example:
class InvoiceController {
    public function store(StoreInvoiceRequest $request)
    {
        try {
            $dto = CreateInvoiceDTO::fromArray($request->validated());
            $invoice = $this->createUseCase->execute(
                $dto,
                tenantContext()->getId(),
            );
            return redirect()->route('invoices.show', $invoice);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }
}
```

### Step 5: Register Binding
```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    $this->app->bind(
        InvoiceRepositoryInterface::class,
        EloquentInvoiceRepository::class,
    );
}
```

### Step 6: ✅ Done!
- Domain entity + interface ✓
- Application DTO + use case ✓
- Infrastructure repository ✓
- Http controller ✓
- Service binding ✓

---

## Common Architecture Mistakes

### ❌ Mistake 1: Eloquent Model in UseCase

```php
// WRONG
class CreateProjectUseCase {
    public function execute(array $data) {
        $project = Project::create($data);  // ← Direct model access!
        return $project;
    }
}

// CORRECT
class CreateProjectUseCase {
    public function __construct(private ProjectRepositoryInterface $repo) {}

    public function execute(CreateProjectDTO $dto, int $tenantId) {
        return $this->repo->create($dto, $tenantId);
    }
}
```

### ❌ Mistake 2: Business Logic in Controller

```php
// WRONG
public function store(Request $request) {
    $validated = $request->validate([...]);
    $project = Project::create($validated);
    $project->owner_id = auth()->id();
    $project->status = 'active';
    $project->save();  // ← Business logic in controller!
    return redirect(...);
}

// CORRECT
public function store(StoreProjectRequest $request) {
    try {
        $dto = CreateProjectDTO::fromArray($request->validated());
        $project = $this->createUseCase->execute($dto, tenantContext()->getId());
        return redirect(...);
    } catch (...) { ... }
}
```

### ❌ Mistake 3: Session Access in UseCase

```php
// WRONG
public function execute(CreateProjectDTO $dto) {
    $tenantId = session('current_tenant_id');  // ← UseCase knows about HTTP!
    return $this->repo->create($dto, $tenantId);
}

// CORRECT
public function execute(CreateProjectDTO $dto, int $tenantId) {
    return $this->repo->create($dto, $tenantId);
}

// In controller:
$project = $this->useCase->execute($dto, tenantContext()->getId());
```

### ❌ Mistake 4: Missing Exception Handling

```php
// WRONG
public function store(Request $request) {
    $this->useCase->execute($request->all());  // Exception escapes!
    return redirect(...);
}

// CORRECT
public function store(Request $request) {
    try {
        // ...
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Something went wrong.')->withInput();
    }
}
```

---

## Summary: Data Flow

```
Form (HTML/API)
  ↓
Controller (try-catch)
  ↓
Request Validation
  ↓
DTO::fromArray() ← snake_case → camelCase
  ↓
UseCase::execute(dto, tenantId)
  ├─ Validate business rules → throw DomainException
  ├─ Call Repository::create()
  └─ Return Entity
  ↓
Controller catches:
├─ DomainException → return with error + input
└─ Exception → log + return generic error
  ↓
Response to user (redirect/view)
```

---

See [PATTERNS.md](./PATTERNS.md) for detailed code patterns.
