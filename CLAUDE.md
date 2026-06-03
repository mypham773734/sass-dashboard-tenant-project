# Project Rules for Claude Code

## Architecture: Clean Architecture + Multi-Tenant SaaS

### Layer Structure

```
app/
├── Domain/           # Pure PHP — NO Laravel dependencies
│   └── {Feature}/
│       ├── Entities/         # Business object, business rules
│       └── Repositories/     # Interface only (contract)
├── Application/      # Orchestration — USE CASES + DTOs
│   └── {Feature}/
│       ├── DTOs/
│       └── UseCases/         # One file = one use case, one execute() method
├── Infrastructure/   # Laravel-specific code
│   └── Persistence/Repositories/  # Eloquent implementations
├── Models/           # Eloquent models — only used in Infrastructure layer
└── Http/             # Presentation layer
    ├── Controllers/
    └── Requests/
```

### Layer Rules

| Layer | Can depend on | Cannot depend on |
|-------|--------------|-----------------|
| Domain | Nothing (pure PHP) | Laravel, Eloquent, Application |
| Application | Domain | Laravel, Eloquent, Infrastructure |
| Infrastructure | Domain, Application, Laravel | — |
| Presentation (Http) | Application, Domain | Infrastructure directly |

### Bindings

Every Repository Interface must be bound in `AppServiceProvider`:
```php
$this->app->bind(
    \App\Domain\{Feature}\Repositories\{Feature}RepositoryInterface::class,
    \App\Infrastructure\Persistence\Repositories\Eloquent{Feature}Repository::class,
);
```

---

## Multi-Tenant Rules

- Every feature belongs to a Tenant — always think about Tenant isolation first
- Tenant context comes from `session('current_tenant_id')`
- All queries scoped to tenant must include `.where('tenant_id', $tenantId)`
- Cross-tenant data access is forbidden — never query without tenant scope
- Pass `tenantId` explicitly from Controller to Use Case — never resolve session inside a Use Case

---

## Controller Rules

### Try-Catch: EVERY method must be wrapped — no exceptions

```php
// Read operations (index, show, edit, create)
public function index()
{
    try {
        $data = $this->someUseCase->execute(...);
        return view('...', compact('data'));
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to load page.');
    }
}

// Write operations (store, update, destroy)
public function store(StoreRequest $request)
{
    try {
        $dto = SomeDTO::fromArray($request->validated());
        $this->createUseCase->execute($dto, ...);
        return redirect()->route('...')->with('success', '...');
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Something went wrong.')->withInput();
    }
}
```

- Read methods: catch `\Exception`, log it, return `back()->with('error', ...)`
- Write methods: catch `\DomainException` first (business rule violations), then `\Exception`
- Always `use Illuminate\Support\Facades\Log`
- Controllers are thin — no business logic, only orchestrate Use Cases

---

## DTO Rules

- Namespace: `App\Application\{Feature}\DTOs\`
- Properties use **camelCase** (PHP convention), not snake_case (DB convention)
- Always provide a `fromArray(array $data): self` static factory
- `fromArray()` handles the snake_case → camelCase mapping

---

## Use Case Rules

- One Use Case = one operation = one `execute()` method
- Inject Repository Interface (never Eloquent model directly)
- Throw `\DomainException` for business rule violations
- Never access `session()`, `auth()`, or HTTP concerns — receive context as parameters

---

## Eloquent Repository Rules

- Only place in the entire codebase allowed to touch Eloquent models
- Must implement the Domain Repository Interface
- Always provide `toEntity(Model $model): Entity` and `toArray(Entity $entity): array` private helpers
- `toEntity()` maps DB snake_case columns → Entity camelCase properties

---

## Answer Conventions (how Claude should respond)

1. Explain the approach before writing code
2. If multiple approaches exist, compare pros and cons
3. Show directory structure when creating new files
4. Flag architectural mistakes and suggest corrections
5. Write clean, comment-free code (unless the WHY is non-obvious)
6. Act as mentor — teach the pattern, don't just solve the immediate problem
