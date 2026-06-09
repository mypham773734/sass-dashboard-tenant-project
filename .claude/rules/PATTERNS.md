# Code Patterns

**Reference:** Copy-paste examples for common patterns.

---

## Table of Contents

1. [Controllers](#controllers)
2. [DTOs](#dtos)
3. [Use Cases](#use-cases)
4. [Repositories](#repositories)

---

## Controllers

**Rule:** Thin controllers, all business logic in UseCase.

### Read Operation (index, show)

```php
public function index()
{
    try {
        $projects = $this->getProjectsUseCase->execute(
            tenantContext()->getId(),
        );
        return view('projects.index', ['projects' => $projects]);
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to load projects.');
    }
}

public function show($id)
{
    try {
        $project = $this->getProjectUseCase->execute($id, tenantContext()->getId());
        return view('projects.show', ['project' => $project]);
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Project not found.');
    }
}
```

### Write Operation (store, update)

```php
public function store(StoreProjectRequest $request)
{
    try {
        $dto = CreateProjectDTO::fromArray($request->validated());
        $project = $this->createProjectUseCase->execute(
            $dto,
            tenantContext()->getId(),
        );
        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created!');
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Something went wrong.')->withInput();
    }
}

public function update(UpdateProjectRequest $request, $id)
{
    try {
        $dto = UpdateProjectDTO::fromArray($request->validated());
        $project = $this->updateProjectUseCase->execute(
            $id,
            $dto,
            tenantContext()->getId(),
        );
        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated!');
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Something went wrong.')->withInput();
    }
}

public function destroy($id)
{
    try {
        $this->deleteProjectUseCase->execute($id, tenantContext()->getId());
        return redirect()->route('projects.index')
            ->with('success', 'Project deleted!');
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage());
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Something went wrong.');
    }
}
```

### Exception Handling Summary

| Operation | DomainException | Exception |
|-----------|-----------------|-----------|
| Catch first? | **Yes** | Then this |
| Meaning | Business rule violated | System error |
| Return | `back()->with('error', $e->getMessage())->withInput()` | `back()->with('error', 'Something went wrong.')->withInput()` |
| Log? | No | **Yes** |
| Keep input? | **Yes** | **Yes** |

---

## DTOs

**Rule:** Properties in camelCase. Always provide `fromArray()` factory.

### DTO Template

```php
namespace App\Application\{Feature}\DTOs;

class Create{Feature}DTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly bool $isActive = true,
        public readonly ?string $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            isActive: (bool)($data['is_active'] ?? true),  // snake → camel
            metadata: $data['metadata'] ?? null,
        );
    }
}
```

### Real Example: CreateProjectDTO

```php
namespace App\Application\Project\DTOs;

class CreateProjectDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly string $status = 'active',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            status: $data['status'] ?? 'active',
        );
    }
}

// Usage in controller:
$dto = CreateProjectDTO::fromArray($request->validated());
// $request->validated() returns form data with snake_case keys
// fromArray() converts to camelCase for domain logic
```

### camelCase vs snake_case Mapping

| Form Field (snake_case) | DTO Property (camelCase) |
|-------------------------|--------------------------|
| `is_active` | `isActive` |
| `owner_id` | `ownerId` |
| `created_at` | `createdAt` |
| `trial_ends_at` | `trialEndsAt` |

---

## Use Cases

**Rule:** One file = one operation = one `execute()` method.

### UseCase Template

```php
namespace App\Application\{Feature}\UseCases;

class Create{Feature}UseCase
{
    public function __construct(
        private readonly {Feature}RepositoryInterface $repository,
    ) {}

    public function execute(
        Create{Feature}DTO $dto,
        int $tenantId,
    ): {Feature}Entity {
        // Validate business rules
        $this->validateRules($dto, $tenantId);

        // Delegate to repository
        return $this->repository->create($dto, $tenantId);
    }

    private function validateRules(Create{Feature}DTO $dto, int $tenantId): void
    {
        if (strlen($dto->name) < 3) {
            throw new \DomainException('Name must be at least 3 characters.');
        }

        // More rules...
    }
}
```

### Real Example: CreateProjectUseCase

```php
namespace App\Application\Project\UseCases;

class CreateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function execute(
        CreateProjectDTO $dto,
        int $tenantId,
    ): ProjectEntity {
        // Validate: tenant exists
        $tenant = $this->tenantRepository->findById($tenantId);
        if (!$tenant) {
            throw new \DomainException('Tenant not found.');
        }

        // Validate: project name unique within tenant
        if ($this->repository->existsByName($dto->name, $tenantId)) {
            throw new \DomainException('Project name already exists.');
        }

        // Validate: business rules
        if (strlen($dto->name) < 3) {
            throw new \DomainException('Name must be at least 3 characters.');
        }

        // Create and return
        return $this->repository->create($dto, $tenantId);
    }
}
```

### Never Read Session

```php
// ❌ WRONG: UseCase knows about HTTP
public function execute(CreateProjectDTO $dto) {
    $tenantId = session('current_tenant_id');  // ← NO!
    return $this->repository->create($dto, $tenantId);
}

// ✅ CORRECT: Pass explicitly
public function execute(CreateProjectDTO $dto, int $tenantId) {
    return $this->repository->create($dto, $tenantId);
}

// In controller:
$project = $this->useCase->execute($dto, tenantContext()->getId());
```

---

## Repositories

**Rule:** Only place allowed to touch Eloquent models.

### Repository Interface (Domain Layer)

```php
namespace App\Domain\{Feature}\Repositories;

interface {Feature}RepositoryInterface
{
    public function findById(int $id): ?{Feature}Entity;
    public function create(Create{Feature}DTO $dto, int $tenantId): {Feature}Entity;
    public function update(int $id, Update{Feature}DTO $dto): {Feature}Entity;
    public function delete(int $id): void;
}
```

### Repository Implementation (Infrastructure Layer)

```php
namespace App\Infrastructure\Persistence\Repositories;

class Eloquent{Feature}Repository implements {Feature}RepositoryInterface
{
    public function findById(int $id): ?{Feature}Entity
    {
        $model = {Feature}::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function create(Create{Feature}DTO $dto, int $tenantId): {Feature}Entity
    {
        $model = {Feature}::create($this->toArray($dto, $tenantId));
        return $this->toEntity($model);
    }

    public function update(int $id, Update{Feature}DTO $dto): {Feature}Entity
    {
        $model = {Feature}::findOrFail($id);
        $model->update($this->toArray($dto));
        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        {Feature}::destroy($id);
    }

    // ← Helper to convert Eloquent → Domain Entity
    private function toEntity({Feature} $model): {Feature}Entity
    {
        return new {Feature}Entity(
            id: $model->id,
            tenantId: $model->tenant_id,  // snake_case → camelCase
            name: $model->name,
            description: $model->description,
            status: $model->status,
            createdAt: $model->created_at,
        );
    }

    // ← Helper to convert DTO → array (for create/update)
    private function toArray(Create{Feature}DTO $dto, ?int $tenantId = null): array
    {
        return [
            'tenant_id' => $tenantId,
            'name' => $dto->name,  // camelCase → snake_case
            'description' => $dto->description,
            'status' => $dto->status,
        ];
    }
}
```

### Real Example: EloquentProjectRepository

```php
namespace App\Infrastructure\Persistence\Repositories;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function findById(int $id): ?ProjectEntity
    {
        $model = Project::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function create(CreateProjectDTO $dto, int $tenantId): ProjectEntity
    {
        $model = Project::create([
            'tenant_id' => $tenantId,
            'name' => $dto->name,
            'description' => $dto->description,
            'status' => $dto->status,
        ]);
        return $this->toEntity($model);
    }

    public function existsByName(string $name, int $tenantId): bool
    {
        return Project::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->exists();
    }

    private function toEntity(Project $model): ProjectEntity
    {
        return new ProjectEntity(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            status: $model->status,
            ownerId: $model->owner_id,
            createdAt: $model->created_at,
        );
    }

    private function toArray(CreateProjectDTO $dto, int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'name' => $dto->name,
            'description' => $dto->description,
            'status' => $dto->status,
        ];
    }
}
```

### Service Container Binding

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    $this->app->bind(
        ProjectRepositoryInterface::class,
        EloquentProjectRepository::class,
    );
}
```

---

## Summary: Data Flow

```
Form → Controller → Request
    ↓
Validation (StoreTenantRequest)
    ↓
DTO::fromArray()
    ↓
UseCase::execute(dto, tenantId)
    ├→ Validate rules
    ├→ Repository::create()
    └→ Return Entity
    ↓
Controller (try-catch)
├→ Success: redirect()
└→ Error: back()->with('error')
    ↓
Response
```

---

See [ARCHITECTURE.md](./ARCHITECTURE.md) for layer structure.
