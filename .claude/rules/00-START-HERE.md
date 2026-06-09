# Start Here: Project Overview

**New dev?** Read this first (5 min). Then refer to [INDEX.md](./INDEX.md) for detailed rules.

---

## 🏗️ What Is This Project?

**Laravel 13 Multi-Tenant SaaS Dashboard**
- Single database, multiple tenants (companies/organizations)
- Users belong to one or more tenants
- Each tenant has projects, users, audit logs
- Clean Architecture (Domain → Application → Infrastructure → Http)
- Role-based access control per tenant

---

## 🎯 Core Concept: Tenant Isolation

Everything is scoped to a tenant:

```
User logs in
  ↓
Tenant is set: session('current_tenant_id')
  ↓
All queries filter by tenant automatically (Global Scope)
  ↓
Cannot see other tenants' data (impossible to cross-contaminate)
```

**Rule:** Always think "which tenant owns this data?" before querying.

---

## 🧬 Architecture: 4 Layers

### Domain Layer (Pure PHP)
- Business objects (Entities)
- Business rules (Repository interfaces)
- **Zero Laravel dependencies**

Example: `app/Domain/Tenant/Entities/TenantEntity.php`

### Application Layer
- DTOs (Data Transfer Objects)
- Use Cases (business logic orchestration)
- **No Laravel, no Eloquent**

Example: `app/Application/Tenant/DTOs/CreateTenantDTO.php`

### Infrastructure Layer
- Repository implementations (Eloquent)
- Queue jobs, services
- **Laravel-specific code**

Example: `app/Infrastructure/Persistence/Repositories/EloquentTenantRepository.php`

### Http/Presentation Layer
- Controllers, requests, routes
- Orchestrates use cases
- **Handles HTTP concerns**

Example: `app/Http/Controllers/TenantController.php`

**Key:** Each layer can only depend on layers below it.

---

## 📂 Project Structure

```
app/
├── Domain/                              # Pure PHP
│   ├── Tenant/
│   │   ├── Entities/TenantEntity.php
│   │   └── Repositories/TenantRepositoryInterface.php
│   ├── User/
│   ├── Project/
│   └── Audit/
├── Application/                         # Orchestration
│   ├── Tenant/
│   │   ├── DTOs/CreateTenantDTO.php
│   │   └── UseCases/CreateTenantUseCase.php
│   ├── User/
│   └── Audit/
├── Infrastructure/                      # Laravel-specific
│   ├── Persistence/Repositories/        # Eloquent implementations
│   │   ├── EloquentTenantRepository.php
│   │   └── EloquentAuditRepository.php
│   ├── Audit/
│   ├── Mail/
│   └── Queue/Jobs/
├── Models/                              # Eloquent (Infrastructure only!)
│   ├── Tenant.php
│   ├── User.php
│   └── Project.php
├── Http/
│   ├── Controllers/TenantController.php
│   ├── Requests/StoreTenantRequest.php
│   └── Middleware/
├── Providers/
│   └── AppServiceProvider.php           # Service bindings
└── Shared/
    └── helpers.php                      # Global helpers
```

---

## ⚡ Key Patterns (Cheat Sheet)

### Create a Feature: 5 Steps

1. **Domain**: Create Entity + RepositoryInterface
   ```php
   app/Domain/{Feature}/Entities/{Feature}Entity.php
   app/Domain/{Feature}/Repositories/{Feature}RepositoryInterface.php
   ```

2. **Application**: Create DTO + UseCase
   ```php
   app/Application/{Feature}/DTOs/Create{Feature}DTO.php
   app/Application/{Feature}/UseCases/Create{Feature}UseCase.php
   ```

3. **Infrastructure**: Implement Repository
   ```php
   app/Infrastructure/Persistence/Repositories/Eloquent{Feature}Repository.php
   ```

4. **Http**: Create Controller
   ```php
   app/Http/Controllers/{Feature}Controller.php
   ```

5. **Binding**: Register in AppServiceProvider
   ```php
   $this->app->bind({Feature}RepositoryInterface::class, Eloquent{Feature}Repository::class);
   ```

---

## 🔴 CRITICAL Rules

### ❌ NEVER Auto-Commit or Push
```bash
❌ WRONG: git commit -m "..."  # without asking
✅ RIGHT: Ask user first → "Should I commit this?"
```

### ❌ NEVER Use Eloquent Models Outside Infrastructure
```php
// ❌ Wrong (in UseCase)
$project = Project::find($id);

// ✅ Correct (via repository)
$project = $this->repository->findById($id);
```

### ❌ NEVER Read Session Inside UseCase
```php
// ❌ Wrong (UseCase should not know about HTTP)
$tenantId = session('current_tenant_id');

// ✅ Correct (pass as parameter)
public function execute(CreateProjectDTO $dto, int $tenantId)
```

### ✅ ALWAYS Wrap Controller Methods in Try-Catch
```php
// ✅ Required
public function store(Request $request)
{
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

### ✅ ALWAYS Pass tenantId Explicitly
```php
// ✅ Correct
$this->createUseCase->execute($dto, tenantContext()->getId());

// ❌ Wrong
$this->createUseCase->execute($dto);  // UseCase reads from session ← BAD
```

---

## 🚀 Quick Commands

```bash
# Setup
composer run setup

# Run everything
composer run dev

# Test
composer run test

# Format code
./vendor/bin/pint --fix

# Database
php artisan migrate:refresh --seed

# Shell
php artisan tinker
```

See [COMMANDS.md](./COMMANDS.md) for full list.

---

## 📖 Next Steps

1. **Read**: [ARCHITECTURE.md](./ARCHITECTURE.md) — Understand layers
2. **Refer**: [PATTERNS.md](./PATTERNS.md) — Code patterns
3. **Use**: [COMMANDS.md](./COMMANDS.md) — Dev workflow
4. **Remember**: [GIT-SAFETY.md](./GIT-SAFETY.md) — No auto-commits!

---

## ❓ Common Questions

**Q: Where do I put business logic?**
A: In Use Cases (`app/Application/{Feature}/UseCases/`), not in controllers.

**Q: Where do I touch the database?**
A: Only in Repository implementations (`app/Infrastructure/Persistence/Repositories/`).

**Q: How do I handle errors?**
A: Throw `\DomainException` in UseCase for business rules. Controller catches and returns to user.

**Q: How do I know if I'm following the architecture?**
A: Ask: "Does this class depend on something it shouldn't?" Check [ARCHITECTURE.md](./ARCHITECTURE.md) layer rules.

**Q: Can I query the database directly in a controller?**
A: No. Always use repository interface.

**Q: When should I commit my changes?**
A: Only when user explicitly says "commit". Never auto-commit. See [GIT-SAFETY.md](./GIT-SAFETY.md).

---

**Ready to code?** → See [ARCHITECTURE.md](./ARCHITECTURE.md) for feature structure or [PATTERNS.md](./PATTERNS.md) for code examples.
