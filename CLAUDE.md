# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**Start here:** `.claude/rules/INDEX.md` — Quick guide to all rules.

---

## 📚 Rules Structure

All project rules are in `.claude/rules/`:

| File | Purpose | Read When |
|------|---------|-----------|
| **[INDEX.md](.claude/rules/INDEX.md)** | Rules guide | First time, confused |
| **[00-START-HERE.md](.claude/rules/00-START-HERE.md)** | Project overview (5 min read) | Getting started |
| **[ARCHITECTURE.md](.claude/rules/ARCHITECTURE.md)** | Layer structure & new features | Building features |
| **[PATTERNS.md](.claude/rules/PATTERNS.md)** | Code patterns (copy-paste) | Implementing code |
| **[COMMANDS.md](.claude/rules/COMMANDS.md)** | Dev commands (cheat sheet) | During development |
| **[GIT-SAFETY.md](.claude/rules/GIT-SAFETY.md)** | ⚠️ Git safety rules | Before committing |

---

## ⚡ 30-Second Summary

**What:** Laravel 13 multi-tenant SaaS with Clean Architecture

**Architecture:**
- Single database, many tenants (tenant_id scoping)
- 4 layers: Domain → Application → Infrastructure → Http
- DTOs in camelCase, database in snake_case
- Repository interfaces (never direct Eloquent in UseCase)
- Try-catch in every controller (DomainException vs Exception)

**Critical Rules:**
- ❌ **NEVER auto-commit or push** (ask user first)
- ❌ Never read session inside UseCase
- ❌ Never use Eloquent outside Infrastructure layer
- ❌ Never call Eloquent models directly in UseCase

See [00-START-HERE.md](.claude/rules/00-START-HERE.md) for full overview.

---

## 🚀 Quick Start

```bash
# Setup
composer run setup

# Dev
composer run dev

# Test
composer run test

# Format
./vendor/bin/pint --fix
```

See [COMMANDS.md](.claude/rules/COMMANDS.md) for all commands.

---

## 🏗️ Building a Feature

1. **Domain:** Entity + RepositoryInterface
2. **Application:** DTO + UseCase
3. **Infrastructure:** Repository implementation (Eloquent)
4. **Http:** Controller
5. **Binding:** Register in AppServiceProvider

See [ARCHITECTURE.md](.claude/rules/ARCHITECTURE.md) for checklist.

---

## ⚠️ Critical Rules

### No Auto-Commit/Push
```bash
❌ WRONG: Commit without asking
✅ RIGHT: Ask "Should I commit this?"
```

### No Eloquent in UseCase
```php
❌ $project = Project::find($id);
✅ $project = $this->repository->findById($id);
```

### No Session in UseCase
```php
❌ $tenantId = session('current_tenant_id');
✅ public function execute(DTO $dto, int $tenantId)
```

### Always Try-Catch Controllers
```php
✅ try { ... } catch (DomainException) { ... } catch (Exception) { ... }
```

See [GIT-SAFETY.md](.claude/rules/GIT-SAFETY.md) for all safety rules.

---

## 📂 Project Structure

```
app/
├── Domain/               # Pure business logic
├── Application/          # DTOs + UseCases
├── Infrastructure/       # Repository implementations
├── Models/              # Eloquent models
├── Http/                # Controllers, routes
└── Providers/           # Service bindings

.claude/rules/           # All rules (this folder)
```

---

## 💡 Common Questions

**Q: Where do I put business logic?**  
A: Use Cases (`app/Application/{Feature}/UseCases/`)

**Q: Where do I query the database?**  
A: Repository implementation (`app/Infrastructure/Persistence/Repositories/`)

**Q: When do I commit?**  
A: Only when user explicitly says "commit". See [GIT-SAFETY.md](.claude/rules/GIT-SAFETY.md)

**Q: What's a DTO?**  
A: Data Transfer Object with camelCase properties. See [PATTERNS.md](.claude/rules/PATTERNS.md)

**Q: How do I handle errors?**  
A: Throw DomainException in UseCase, catch in controller. See [PATTERNS.md](.claude/rules/PATTERNS.md)

---

## 📖 Resources

- **Full rules:** `.claude/rules/` folder
- **Project docs:** `docs/product/` folder
- **README:** Project overview, API docs
- **Laravel:** https://laravel.com/docs/13.x

---

**Last Updated:** 2026-06-09

**Next:** Read [.claude/rules/INDEX.md](.claude/rules/INDEX.md) or [.claude/rules/00-START-HERE.md](.claude/rules/00-START-HERE.md)
