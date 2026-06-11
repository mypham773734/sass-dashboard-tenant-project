# Naming Conventions

**Rule:** All project files should use lowercase with hyphens (kebab-case).

---

## Where Docs Go

| Folder | Purpose |
|---|---|
| `docs/plan/` | Feature/refactor **plans** (design docs written before implementation) |
| `docs/product/<feature>/` | Finalized feature docs (readme, requirements, architecture, implementation steps) |

When asked to "lên plan" (create a plan) for a feature or refactor, save it to `docs/plan/<feature-name>.md` (lowercase kebab-case).

## Documentation Files & Folders

All `.md` files and folder names in `docs/` and `.claude/rules/` must be **lowercase**:

### Folders: lowercase with hyphens

```
✅ CORRECT (lowercase folders)
docs/product/
├── audit-email/      ← lowercase
├── audit-system/     ← lowercase
├── mail-service/     ← lowercase
├── permission-rbac/  ← lowercase
└── user-profile/     ← lowercase

❌ WRONG (ANY of these is incorrect)
├── AUDIT_EMAIL/      ← UPPERCASE
├── AuditSystem/      ← PascalCase
├── Mail-Service/     ← Mixed case
├── PERMISSION_RBAC/  ← UPPERCASE
└── UserProfile/      ← PascalCase
```

### Files: lowercase with hyphens (or no extension for README)

```
✅ CORRECT (lowercase files)
docs/product/mail-service/
├── readme.md                  ← lowercase readme
├── 01-requirements.md
├── 02-architecture.md
└── 03-implementation-plan.md

.claude/rules/
├── index.md
├── 00-start-here.md
├── architecture.md
├── patterns.md
├── commands.md
├── git-safety.md
└── naming-conventions.md

❌ WRONG (ANY of these is incorrect)
├── README.md                  ← uppercase README
├── 01-REQUIREMENTS.md         ← UPPERCASE
├── ARCHITECTURE.MD            ← UPPERCASE
├── Index.md                   ← PascalCase
├── Git-Safety.md              ← PascalCase
└── Implementation-Plan.md     ← PascalCase
```

---

## Naming Pattern

### For Documentation Folders

**Pattern:** `kebab-case` (all lowercase, hyphens for spaces)

| Type | Example |
|------|---------|
| Feature folders | `mail-service/`, `audit-email/` |
| System folders | `audit-system/`, `permission-rbac/` |
| Feature docs | `user-profile/`, `permission-rbac/` |

### For Documentation Files

**Pattern:** `kebab-case.md` (all lowercase, hyphens for spaces)

| Type | Example |
|------|---------|
| Index/Overview | `index.md`, `readme.md` |
| Feature docs | `mail-service.md`, `audit-system.md` |
| Rule files | `git-safety.md`, `naming-conventions.md` |
| Step guides | `01-requirements.md`, `02-architecture.md` |
| Implementation | `03-implementation-plan.md` |

### For Code Files (PHP, JS, etc.)

**Pattern:** PascalCase for classes, camelCase for files/variables (Laravel convention)

```php
// ✅ CORRECT (Laravel convention)
app/Models/User.php              // filename: PascalCase
class User extends Model {}      // classname: PascalCase

app/Http/Controllers/UserController.php
class UserController extends Controller {}

// ✅ CORRECT
app/Domain/User/Entities/UserEntity.php
class UserEntity {}

// ✅ CORRECT
$userName = "John";              // variables: camelCase
$userEmail = "john@example.com";
```

---

## Why Lowercase for Docs?

### Benefits

✅ **Consistent** — No confusion between CamelCase, UPPERCASE, lowercase across entire docs/  
✅ **Searchable** — Easy to grep: `grep -r "mail-service" docs/`  
✅ **URL-safe** — If docs become URLs: `/docs/mail-service/readme` works (no case issues)  
✅ **Cross-platform** — Windows/Mac/Linux all treat lowercase consistently  
✅ **Git-friendly** — Prevents rename-only commits (case-sensitive filesystems)  
✅ **Professional** — Standard practice for documentation systems  

### Grep Examples

```bash
# ✅ Easy to find (lowercase)
grep -r "git-safety" .claude/rules/
grep -r "start-here" .claude/rules/

# ❌ Hard to find (mixed case, need --ignore-case flag)
grep -r "GIT-SAFETY\|Git-Safety\|git-safety" .claude/rules/
```

---

## Exception: Code Comments

When documenting code in comments or docstrings, use the actual code's naming:

```php
/**
 * Implements RepositoryInterface contract
 * See: .claude/rules/patterns.md for Repository pattern
 */
class EloquentUserRepository implements UserRepositoryInterface
{
    // ✅ Correct: file is named patterns.md (lowercase)
}
```

---

## Applying This Rule

### When Renaming Files/Folders

1. Check location: is it in `docs/`, `.claude/`, or similar?
2. If yes → **use lowercase with hyphens**
3. If no (code files) → use existing convention (PascalCase for classes, etc.)

### When Creating New Docs

Always use: `lowercase-with-hyphens/` for folders, `lowercase-with-hyphens.md` for files

```bash
# ✅ Correct (folders AND files lowercase)
mkdir docs/product/my-feature/
touch docs/product/my-feature/01-requirements.md
touch docs/product/my-feature/02-architecture.md

mkdir .claude/rules/
touch .claude/rules/new-feature-guide.md

# ❌ Wrong
mkdir docs/product/MyFeature/
touch docs/product/MyFeature/01-REQUIREMENTS.md
mkdir .claude/rules/
touch .claude/rules/NewFeatureGuide.MD
```

### Updating Links

When linking to docs, use lowercase:

```markdown
✅ Correct (folders and files lowercase)
See [architecture.md](./architecture.md) for details.
See [git-safety.md](./../git-safety.md) for rules.
See [mail-service](./mail-service/readme.md) for feature docs.

❌ Wrong
See [ARCHITECTURE.md](./ARCHITECTURE.md) for details.
See [Git-Safety.md](./../Git-Safety.md) for rules.
See [Mail-Service](./MAIL_SERVICE/README.md) for feature docs.
```

---

## Summary

| Type | Convention | Example |
|------|-----------|---------|
| **Docs** | lowercase-kebab-case | `git-safety.md` |
| **Code Classes** | PascalCase | `UserRepository.php` |
| **Code Variables** | camelCase | `$userName` |
| **Config Keys** | snake_case | `mail_service` |
| **Database Columns** | snake_case | `tenant_id` |

---

See [index.md](./index.md) for rule navigation.
