# Development Commands

**Cheat sheet:** Copy-paste commands for common tasks.

---

## Setup (First Time)

```bash
# One-liner: everything
composer run setup

# Manual:
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install --ignore-scripts
npm run build
```

---

## Running the App

```bash
# Option 1: Just server
php artisan serve
# Opens: http://127.0.0.1:8000

# Option 2: Everything (recommended)
composer run dev
# Runs: server + queue + logs + Vite
# Exit: Ctrl+C
```

---

## Testing

```bash
# All tests (clears cache first)
composer run test

# Just run tests
php artisan test

# Specific file
php artisan test tests/Feature/Auth/LoginTest.php

# Specific test method
php artisan test tests/Feature/Auth/LoginTest.php --filter=test_login

# By directory
php artisan test tests/Unit
php artisan test tests/Feature

# With coverage
php artisan test --coverage
```

---

## Code Quality

```bash
# Format code (auto-fix)
./vendor/bin/pint --fix

# Check without fixing
./vendor/bin/pint --test

# List routes
php artisan route:list

# Show bindings
php artisan container:show
```

---

## Database

```bash
# Run migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Reset everything
php artisan migrate:reset

# Reset + migrate + seed
php artisan migrate:refresh --seed

# Check status
php artisan migrate:status

# Seed only
php artisan db:seed
```

---

## Debugging

```bash
# Interactive shell
php artisan tinker
# Examples:
# >>> $tenant = Tenant::first();
# >>> $tenant->users;
# >>> DB::listen(fn($q) => dump($q->sql));

# Watch logs real-time
php artisan pail

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

---

## Queue

```bash
# Listen to queue
php artisan queue:listen --tries=1 --timeout=0

# Flush failed jobs
php artisan queue:flush

# Restart
php artisan queue:restart
```

---

## One-Liners

```bash
# Reset DB + migrate + seed + clear cache
php artisan migrate:reset && php artisan migrate && php artisan db:seed && php artisan cache:clear

# Format + test
./vendor/bin/pint --fix && php artisan test

# Serve + watch logs
php artisan serve & php artisan pail
```

---

## Common Workflows

### "I want to work on the project"
```bash
composer run dev
# In another terminal: composer run test
```

### "I want to reset everything"
```bash
php artisan migrate:reset
php artisan migrate --seed
php artisan cache:clear
```

### "I want to check if tests pass"
```bash
composer run test
```

### "I want to format my code"
```bash
./vendor/bin/pint --fix
```

### "I want to debug a query"
```bash
php artisan tinker
>>> DB::listen(function ($query) {
    \Log::info($query->sql);
    \Log::info($query->bindings);
});
>>> Tenant::all();  // See the query in logs
```

### "I want to see all my changes"
```bash
git diff
# or staged:
git diff --staged
```

---

See [00-start-here.md](././00-start-here.md) for project overview.
