# 🏗️ Dashboard SASS - Multi-Tenant SaaS Platform

<div align="center">

[![Laravel 13](https://img.shields.io/badge/Laravel-13.0+-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![Multi-Tenant](https://img.shields.io/badge/Architecture-Multi--Tenant-40E0D0?style=flat-square)](https://laravel.com)
[![Livewire 4](https://img.shields.io/badge/Livewire-4.x-FB70A9?style=flat-square&logo=livewire)](https://livewire.laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

*A modern SaaS platform with a Single Database multi-tenant architecture, supporting project and user management.*

[📖 Documentation](#-documentation) • [🚀 Quick Start](#-quick-start) • [🏗️ Architecture](#-architecture) • [📂 API Routes](#-api-documentation) • [🛠️ Troubleshooting](#-troubleshooting)

</div>

---

## 📋 Table of Contents

- [🎯 Overview](#-overview)
- [✨ Key Features](#-key-features)
- [🏗️ Architecture](#-architecture)
- [📊 Tenant Flow Diagram](#-tenant-request-flow)
- [🚀 Installation](#-installation)
- [📂 API Documentation](#-api-documentation)
- [⚙️ Artisan Commands](#-artisan-commands)
- [🛠️ Troubleshooting](#-troubleshooting)
- [📚 Additional Documentation](#-additional-documentation)

---

## 🎯 Overview

**Dashboard SASS** is a SaaS (Software as a Service) platform built on Laravel 13 with a **Multi-Tenant Single Database** architecture. The application supports:

✅ Creating and managing multiple **Tenants** (Companies/Organisations)  
✅ Role-based access control per tenant  
✅ Secure data isolation between tenants  
✅ **Project** management scoped to each tenant  
✅ Realtime UI with **Livewire** and **Alpine.js**  
✅ Modern styling with **Tailwind CSS** and **SASS**

### Multi-Tenant Architecture: Single Database

Instead of creating a separate database for each tenant (Multi-DB), we use a **Single Database** with a `tenant_id` column across relevant tables:

```sql
-- Example: Projects table
CREATE TABLE projects (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,         -- ← Tenant discriminator key
    owner_id BIGINT NOT NULL,
    name VARCHAR(255),
    description TEXT,
    status VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

**Benefits:**
- 💰 Lower server costs (no per-tenant database)
- 🚀 Simple full-data backup and restore
- 🔄 Easier scalability
- 🔐 Global Scope automatically filters data per user

---

## ✨ Key Features

### 🟢 Tenant Management
- ✅ Create a new tenant (`POST /admin/tenant`)
- ✅ List tenants (`GET /admin/tenant`)
- ✅ Update a tenant (`TenantController::update`)
- ✅ Soft-delete a tenant
- ✅ Trial Period mechanism (`trial_ends_at`)
- ✅ Per-tenant JSON settings

### 👥 User & Role Management
- ✅ Create users and assign them to tenants
- ✅ Role-based access (admin, member, viewer)
- ✅ Roles stored in the `tenant_user` pivot table via the `role` column
- ✅ Spatie Laravel Permission integration

### 📁 Project Management
- ✅ Create projects scoped to a tenant
- ✅ Track `owner_id` (creator)
- ✅ Project status (active, archived, etc.)
- ✅ Project description & metadata
- ✅ Soft-delete projects

### 🔐 Multi-Tenancy Features
- ✅ **Global Scope**: Automatically filters queries by the current user
- ✅ **Tenant Isolation**: Data is securely isolated between tenants
- ✅ **Automatic Filtering**: Eloquent queries only return data for the current tenant
- ✅ **Permission Integration**: Works with Spatie Permission

### 🎨 Frontend
- ✅ Admin Dashboard UI
- ✅ Blade Templates + Livewire Components
- ✅ Tailwind CSS + SASS
- ✅ Alpine.js for interactivity
- ✅ Custom Auth UI

### 🔧 Backend Architecture
- ✅ **Service Layer Pattern**: `TenantService` implements an interface
- ✅ **Repository Pattern**: Contracts + Implementations
- ✅ **DTO Pattern**: `CreateTenantDTO` for data transfer
- ✅ **Dependency Injection**: Bindings registered in `AppServiceProvider`
- ✅ **Custom Request Validation**: `StoreTenantRequest`

---

## 🏗️ Architecture

### Directory Structure

```
dashboard-sass/
├── app/
│   ├── DTOs/                          # Data Transfer Objects
│   │   └── tenants/
│   │       └── CreateTenantDTO.php    # DTO for tenant creation
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── TenantController.php   # Tenant management
│   │   │   ├── ProfileController.php
│   │   │   └── CustomAuth/
│   │   │       └── AuthenticatedSessionController.php
│   │   └── Requests/
│   │       └── StoreTenantRequest.php # Validation request
│   │
│   ├── Models/
│   │   ├── Tenant.php                 # Tenant model (main)
│   │   ├── User.php                   # User model
│   │   ├── Project.php                # Project model
│   │   └── Scopes/
│   │       └── TenantScope.php        # Global scope for tenant isolation
│   │
│   ├── Services/
│   │   ├── Contracts/
│   │   │   └── TenantServiceInterface.php
│   │   └── Impl/
│   │       └── TenantService.php      # Business logic
│   │
│   ├── Repositories/
│   │   ├── Contracts/                 # Repository interfaces
│   │   └── Impl/                      # Repository implementations
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php     # Service container bindings
│   │
│   ├── Traits/                        # Reusable traits
│   └── View/
│       └── Components/                # View components
│
├── config/                            # Configuration files
├── database/
│   ├── migrations/                    # Database schema
│   │   ├── 2026_04_09_103654_create_tenants_table.php
│   │   ├── 2026_04_09_103902_create_tenant_user_table.php
│   │   └── 2026_04_09_110750_create_projects_table.php
│   ├── factories/
│   │   └── UserFactory.php
│   └── seeders/
│       └── DatabaseSeeder.php
│
├── resources/
│   ├── js/                            # JavaScript & Vite
│   │   ├── app.js
│   │   ├── bootstrap.js
│   │   └── bases/
│   │
│   ├── sass/                          # SASS/CSS
│   │   └── app.scss
│   │
│   └── views/                         # Blade templates
│       ├── admin/                     # Admin pages
│       ├── auth/                      # Auth pages
│       ├── components/                # Reusable components
│       ├── layouts/                   # Layout templates
│       └── dashboard.blade.php
│
├── routes/
│   ├── web.php                        # Web routes
│   ├── auth.php                       # Auth routes (Breeze)
│   └── console.php
│
├── tests/                             # Tests
│   ├── Feature/
│   │   ├── Auth/
│   │   └── ProfileTest.php
│   └── Unit/
│
├── public/                            # Public assets
├── storage/                           # Logs, cache, sessions
├── vite.config.js                     # Vite configuration
├── tailwind.config.js                 # Tailwind configuration
├── postcss.config.js                  # PostCSS configuration
├── phpunit.xml                        # PHPUnit configuration
├── composer.json                      # PHP dependencies
├── package.json                       # JS dependencies
└── artisan                            # Laravel CLI
```

### Database Schema

```sql
-- Tenants Table
CREATE TABLE tenants (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT true,
    trial_ends_at TIMESTAMP NULL,
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
);

-- Tenant-User Relationship (Many-to-Many)
CREATE TABLE tenant_user (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role VARCHAR(50) NOT NULL,        -- admin, member, viewer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant_user (tenant_id, user_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id)
);

-- Projects Table
CREATE TABLE projects (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,        -- ← Tenant discriminator key
    owner_id BIGINT NOT NULL,         -- ← Creator
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_owner (owner_id),
    INDEX idx_status (status)
);
```

---

## 📊 Tenant Request Flow

```mermaid
graph TD
    A["🌐 User Request<br/>GET /admin/tenant"] --> B["🔐 Middleware: auth<br/>Authenticate user"]
    B --> C{["Authenticated?"]}
    C -->|No| D["❌ Redirect to login"]
    C -->|Yes| E["📝 TenantController::index<br/>Tenant::all"]
    
    E --> F["🔍 Eloquent Query Builder"]
    F --> G["⏱️ Global Scope Applied<br/>TenantScope::apply"]
    
    G --> H{["Auth Check"]}
    H -->|Auth not found| I["⚠️ No Results"]
    H -->|Auth found| J["🔎 whereHas users<br/>where user_id = auth->id"]
    
    J --> K["📊 Database Query<br/>SELECT * FROM tenants<br/>WHERE id IN...<br/>AND user_id = ?"]
    
    K --> L["✅ Results Filtered<br/>Only tenant_user relationships"]
    L --> M["📄 Return View<br/>admin.pages.tenant.index<br/>with tenants data"]
    
    M --> N["🎨 Render Blade<br/>Display tenant list"]
    N --> O["📤 HTTP Response 200"]

    style A fill:#e1f5ff
    style G fill:#fff9c4
    style K fill:#f3e5f5
    style O fill:#c8e6c9
```

### Flow Details

#### 1️⃣ **Request Phase**
```php
// User requests: GET /admin/tenant
Route::middleware('auth')->group(function () {
    Route::resource('/tenant', TenantController::class);
});
```

#### 2️⃣ **Controller Phase**
```php
public function index()
{
    $tenants = Tenant::all();  // 🔑 TenantScope applied automatically
    return view('admin.pages.tenant.index', [
        'tenants' => $tenants
    ]);
}
```

#### 3️⃣ **Model & Global Scope Phase**
```php
// app/Models/Tenant.php
public static function booted()
{
    static::addGlobalScope(new TenantScope);
}

// app/Models/Scopes/TenantScope.php
public function apply(Builder $builder, Model $model): void
{
    if(auth()->check()) {
        // Only return tenants the current user belongs to
        $builder->whereHas('users', function ($q) {
            $q->where('users.id', auth()->id());
        });
    }
}
```

#### 4️⃣ **Query Execution**
```sql
SELECT * FROM `tenants`
WHERE EXISTS (
    SELECT 1 FROM `users`
    INNER JOIN `tenant_user` ON `users`.`id` = `tenant_user`.`user_id`
    WHERE `tenants`.`id` = `tenant_user`.`tenant_id`
    AND `users`.`id` = 123  -- ← auth()->id()
)
```

#### 5️⃣ **Response Phase**
```php
// View receives the filtered tenants array
// Only the current user's tenants are displayed
```

---

## 🚀 Installation

### 📋 System Requirements

- **PHP**: 8.3 or higher
- **Composer**: Latest version
- **Node.js**: 16+ (for Vite & npm)
- **Database**: SQLite (default) or MySQL 8.0+ / PostgreSQL 12+
- **Git**: To clone the repository

### ⚡ Quick Start (5 minutes)

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/dashboard-sass.git
cd dashboard-sass

# 2. Run the automated setup script
composer run setup

# ✅ Setup complete! The application is ready.
```

### 📖 Step-by-Step Guide

#### **Step 1: Prepare the Environment**

```bash
# 1.1 Clone the repository
git clone https://github.com/yourusername/dashboard-sass.git
cd dashboard-sass

# 1.2 Copy .env.example to .env
cp .env.example .env

# 1.3 Edit .env for your database (optional)
# SQLite (Default):
DB_CONNECTION=sqlite

# MySQL:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dashboard_sass
DB_USERNAME=root
DB_PASSWORD=your_password

# PostgreSQL:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dashboard_sass
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

#### **Step 2: Install Dependencies**

```bash
# 2.1 Install PHP dependencies
composer install

# 2.2 Generate application key
php artisan key:generate

# 2.3 Install Node.js dependencies
npm install --ignore-scripts

# Or if using pnpm/yarn
pnpm install
# or
yarn install
```

#### **Step 3: Configure the Database**

```bash
# 3.1 Create database (if using MySQL/PostgreSQL)
# MySQL:
mysql -u root -p -e "CREATE DATABASE dashboard_sass;"

# PostgreSQL:
createdb dashboard_sass

# 3.2 Run migrations
php artisan migrate

# 3.3 (Optional) Run seeders
php artisan db:seed
```

#### **Step 4: Build Frontend Assets**

```bash
# 4.1 Dev mode (watch for changes)
npm run dev

# Or build for production
npm run build
```

#### **Step 5: Run the Application**

```bash
# Option 1: PHP built-in server
php artisan serve

# Output: Starting Laravel development server: http://127.0.0.1:8000

# Option 2: Full stack (server + queue + logs + frontend dev)
composer run dev

# Output:
# ✓ server  | Laravel development server running
# ✓ queue   | Processing jobs...
# ✓ logs    | Listening to logs...
# ✓ vite    | Vite starting...
```

#### **Step 6: Access the Application**

```
🌐 Open your browser and go to:
   http://localhost:8000

📝 Register an account or log in
💼 Create your first tenant at /admin/tenant
✅ Start managing projects!
```

### 🐳 Docker Installation (Optional)

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/dashboard-sass.git
cd dashboard-sass

# 2. Copy .env
cp .env.example .env

# 3. Build Docker image
docker-compose build

# 4. Start containers
docker-compose up -d

# 5. Run commands inside the container
docker-compose exec app composer install
docker-compose exec app php artisan migrate
docker-compose exec app npm install
docker-compose exec app npm run build

# 6. Access the application
# http://localhost:80
```

#### `docker-compose.yml` (create this file)

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "80:80"
    environment:
      - DB_CONNECTION=mysql
      - DB_HOST=db
      - DB_PORT=3306
      - DB_DATABASE=dashboard_sass
      - DB_USERNAME=root
      - DB_PASSWORD=secret
    depends_on:
      - db
    volumes:
      - .:/var/www/html

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=secret
      - MYSQL_DATABASE=dashboard_sass
    ports:
      - "3306:3306"
    volumes:
      - db_storage:/var/lib/mysql

volumes:
  db_storage:
```

#### `Dockerfile`

```dockerfile
FROM php:8.3-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite

RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

RUN a2enmod rewrite

COPY . .

RUN composer install --no-dev
RUN npm install && npm run build

EXPOSE 80
```

---

## 📂 API Documentation

### 🔑 Authentication Routes

#### Login - Custom Auth
```http
POST /custom-login
Content-Type: application/x-www-form-urlencoded

email=user@example.com&password=password123

Response: 302 Redirect to /admin/dashboard
```

#### Logout
```http
POST /admin/custom-logout
Authorization: Bearer {session_id}

Response: 302 Redirect to /
```

---

### 🏢 Tenant Management Routes

#### 📋 List Tenants
```http
GET /admin/tenant
Authorization: Authenticated

Response: 200 OK
Content-Type: text/html

HTML: admin.pages.tenant.index with $tenants variable
```

**Controller Logic:**
```php
public function index()
{
    $tenants = Tenant::all();  // Global Scope filters by current user
    return view('admin.pages.tenant.index', ['tenants' => $tenants]);
}
```

**Query Generated:**
```sql
SELECT * FROM `tenants`
WHERE EXISTS (
    SELECT 1 FROM `users`
    INNER JOIN `tenant_user` ON `users`.`id` = `tenant_user`.`user_id`
    WHERE `tenants`.`id` = `tenant_user`.`tenant_id`
    AND `users`.`id` = {authenticated_user_id}
)
```

---

#### ✨ Create Tenant Form
```http
GET /admin/tenant/create
Authorization: Authenticated

Response: 200 OK
Content-Type: text/html

HTML: admin.pages.tenant.create form
```

---

#### ➕ Create New Tenant
```http
POST /admin/tenant
Authorization: Authenticated
Content-Type: application/x-www-form-urlencoded

name=Acme Corp&slug=acme-corp&is_active=1

Request Validation (StoreTenantRequest):
- name: required|string|max:255
- slug: required|string|unique:tenants|max:255
- is_active: boolean
- trial_ends_at: nullable|date

Response: 302 Redirect
Location: /admin/tenant
Session: success message
```

**Service Layer:**
```php
// app/Services/Impl/TenantService.php
public function createTenant(CreateTenantDTO $dto)
{
    $data = $dto->toArray();
    return Tenant::create($data);  // ← Creates tenant
}

// In Controller:
$dto = CreateTenantDTO::fromArray($request->all());
$tenant = $this->tenantService->createTenant($dto);
$tenant->users()->attach($user->id, ['role' => 'admin']);
```

---

#### 📝 Update Tenant
```http
PATCH /admin/tenant/{id}
Authorization: Authenticated
Content-Type: application/x-www-form-urlencoded

name=Updated Name&is_active=0

Response: 302 Redirect
Status: In Development (endpoint exists but not fully implemented)
```

---

#### 🗑️ Delete Tenant
```http
DELETE /admin/tenant/{id}
Authorization: Authenticated

Response: 302 Redirect
Status: In Development (soft delete)
```

---

### 📊 Relationships & Data Structure

#### Tenant Entity
```json
{
  "id": 1,
  "name": "Acme Corporation",
  "slug": "acme-corp",
  "is_active": true,
  "trial_ends_at": "2026-05-09T10:36:54Z",
  "settings": {
    "theme": "dark",
    "language": "en"
  },
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "pivot": {
        "tenant_id": 1,
        "user_id": 1,
        "role": "admin"
      }
    }
  ],
  "projects": [
    {
      "id": 1,
      "name": "Website Redesign",
      "description": "Q2 2026 website overhaul",
      "status": "active",
      "owner_id": 1
    }
  ],
  "created_at": "2026-04-09T10:36:54Z",
  "updated_at": "2026-04-09T10:36:54Z"
}
```

#### User-Tenant Relationship
```json
{
  "user_id": 1,
  "tenant_id": 1,
  "role": "admin",  // Possible values: admin, member, viewer
  "created_at": "2026-04-09T10:39:02Z",
  "updated_at": "2026-04-09T10:39:02Z"
}
```

#### Project Entity
```json
{
  "id": 1,
  "tenant_id": 1,
  "owner_id": 1,
  "name": "Website Redesign",
  "description": "Q2 2026 website overhaul",
  "status": "active",  // active | archived | completed
  "created_at": "2026-04-09T11:07:50Z",
  "updated_at": "2026-04-09T11:07:50Z",
  "deleted_at": null
}
```

---

### 🔐 Security Headers & CSRF

All POST/PATCH/DELETE requests require a CSRF token:

```html
<!-- Blade Template -->
<form method="POST" action="/admin/tenant">
    @csrf
    <input type="text" name="name" required>
    <button type="submit">Create Tenant</button>
</form>
```

```javascript
// In AJAX Request
const token = document.querySelector('meta[name="csrf-token"]').content;
fetch('/admin/tenant', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});
```

---

## ⚙️ Artisan Commands

### 🚀 Development

```bash
# Start the development server (PHP built-in)
php artisan serve
# Output: http://127.0.0.1:8000

# Run everything (server + queue + logs + frontend dev)
composer run dev

# Open Tinker (interactive shell)
php artisan tinker

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 📊 Database

```bash
# Run all migrations
php artisan migrate

# Rollback the last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Refresh the database (reset + migrate)
php artisan migrate:refresh

# Seed the database
php artisan db:seed

# Refresh + Seed
php artisan migrate:refresh --seed

# Check migration status
php artisan migrate:status
```

### 🛠️ Tenant Management

```bash
# Custom command (if created)
php artisan tenant:create --name="New Company" --slug="new-company"

# List all tenants
php artisan tinker
>>> Tenant::all();

# Get a specific tenant
>>> Tenant::where('slug', 'acme-corp')->first();

# Add a user to a tenant
>>> $tenant = Tenant::find(1);
>>> $tenant->users()->attach(2, ['role' => 'admin']);
```

### ✉️ Mail

```bash
# Test mail configuration
php artisan mail:show

# Check the mail queue
php artisan queue:listen --tries=1 --timeout=0
```

### 🧪 Testing

```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage

# Run a specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run unit tests only
php artisan test tests/Unit/

# Run feature tests only
php artisan test tests/Feature/
```

### 📋 Code Quality

```bash
# Lint PHP code
./vendor/bin/pint

# Lint and fix automatically
./vendor/bin/pint --fix

# Check code with PHPStan (if installed)
php artisan ide-helper:generate

# List routes
php artisan route:list

# Show model info
php artisan model:show
```

### 🔍 Debugging

```bash
# Monitor logs in real time
php artisan pail

# Log SQL queries
php artisan tinker
>>> DB::listen(function ($query) { dump($query->sql); });

# Dump database config
php artisan tinker
>>> config('database');

# List all models
php artisan model:list
```

### 🗑️ Cleanup

```bash
# Clear old logs
php artisan logs:clear

# Clear failed jobs
php artisan queue:failed-table
php artisan queue:flush

# Restart the queue worker
php artisan queue:restart
```

---

## 🛠️ Troubleshooting

### 🔴 Tenant Isolation Issues

#### ❌ Error: User sees data from another tenant

**Cause:**
- Global Scope not applied
- `TenantScope::apply()` not being called
- User is not authenticated

**Fix:**

```php
// In Model:
public static function booted()
{
    static::addGlobalScope(new TenantScope);  // ✅ Make sure this exists
}

// Verify TenantScope:
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if(auth()->check()) {
            $builder->whereHas('users', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }
    }
}

// Debug:
php artisan tinker
>>> auth()->user();  // Check if the user is authenticated
>>> Tenant::all();   // Check if the scope is applied
```

#### ❌ Error: Certain models are not filtered by tenant

**Cause:**
- Model does not have the `TenantScope` global scope
- Model does not have a `belongsTo(Tenant::class)` relationship

**Fix:**

```php
// ❌ Wrong (no scope)
class Project extends Model
{
    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}

// ✅ Correct (with scope)
class Project extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
```

---

### 🔴 Migration Errors

#### ❌ Error: "SQLSTATE[HY000]: General error: 1 Error while executing query"

**Cause:**
- SQLite does not enforce foreign keys by default
- Or the referenced table does not exist yet

**Fix:**

```php
// In migration file:
Schema::enableForeignKeyConstraints();  // ← Enable before migrating

Schema::create('projects', function (Blueprint $table) {
    // ... columns ...
});

Schema::disableForeignKeyConstraints();

// Or in config/database.php:
'sqlite' => [
    'driver' => 'sqlite',
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => true,  // ← Add this line
],
```

#### ❌ Error: "Table already exists"

**Fix:**

```bash
# Reset database
php artisan migrate:reset

# Or delete the database file (SQLite)
rm database/database.sqlite
php artisan migrate
```

---

### 🔴 Permission / Role Errors

#### ❌ Error: User has no role in a tenant

**Cause:**
- User has not been added to the `tenant_user` table
- Role was not set correctly

**Fix:**

```php
// ✅ Attach user to tenant on creation
$tenant = Tenant::create($data);
$tenant->users()->attach(auth()->id(), ['role' => 'admin']);

// ✅ Check if user belongs to a tenant
$user = User::find(1);
$user->tenants;  // List of tenants for this user

// ✅ Check role
$tenant = Tenant::find(1);
$tenant->users()->where('user_id', auth()->id())->first();
// Output: User object with pivot.role
```

---

### 🔴 Tenant Settings Errors

#### ❌ Error: JSON settings not working

**Fix:**

```php
// ✅ Declare cast in the model
class Tenant extends Model
{
    protected $casts = [
        'settings' => 'array',  // ← Automatically converts JSON
    ];
}

// ✅ Use settings
$tenant = Tenant::find(1);
$tenant->settings['theme'] = 'dark';
$tenant->save();

// ✅ Query by settings
Tenant::where('settings->theme', 'dark')->get();
```

---

### 🔴 Trial Period Errors

#### ❌ Error: Tenant subscription has expired

**Fix:**

```php
// ✅ Check trial status
$tenant = Tenant::find(1);
if ($tenant->trial_ends_at && $tenant->trial_ends_at < now()) {
    return response()->json(['message' => 'Trial expired'], 403);
}

// ✅ Inside the scope
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if(auth()->check()) {
            $builder->whereHas('users', function ($q) {
                $q->where('users.id', auth()->id());
            })->where('is_active', true);  // ← Active tenants only
            // ->where('trial_ends_at', '>', now());  // ← Optional
        }
    }
}
```

---

### 🔴 Performance / N+1 Query Errors

#### ❌ Error: Too many queries when loading tenants

**Cause:**
- Eager loading not used
- Global Scope causing N+1 queries

**Fix:**

```php
// ❌ Wrong (N+1 queries)
$tenants = Tenant::all();
foreach ($tenants as $tenant) {
    echo $tenant->users()->count();  // ← A query for every tenant
}

// ✅ Correct (eager load)
$tenants = Tenant::with('users', 'projects')->get();
foreach ($tenants as $tenant) {
    echo count($tenant->users);  // ← No extra queries
}

// ✅ Debug queries
DB::listen(function ($query) {
    \Log::info($query->sql, $query->bindings);
});
```

---

### 🔴 View / Blade Errors

#### ❌ Error: View not found

**Cause:**
- Incorrect view path
- View file does not exist

**Fix:**

```php
// ✅ Check the path
// resources/views/admin/pages/tenant/index.blade.php
// Corresponds to: admin.pages.tenant.index

// ✅ Debug
php artisan view:clear
php artisan config:clear
php artisan cache:clear

// ✅ Correct usage:
return view('admin.pages.tenant.index', [
    'tenants' => $tenants
]);
```

---

### 🔴 Asset Errors (CSS/JS)

#### ❌ Error: CSS/JS not loading

**Cause:**
- Vite dev server is not running
- Assets have not been built

**Fix:**

```bash
# Dev mode (watch)
npm run dev

# Or build for production
npm run build

# Check manifest
ls public/build/manifest.json

# Clear cache
php artisan cache:clear
```

---

## 📚 Additional Documentation

### 🔗 Official Documentation

- 📖 [Laravel Documentation](https://laravel.com/docs/13.x)
- 📘 [Livewire Documentation](https://livewire.laravel.com)
- 🎨 [Tailwind CSS](https://tailwindcss.com)
- ⚙️ [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)

### 📁 Project Files

- [Architecture Diagram](#-architecture)
- [Database Schema](#database-schema)
- [API Routes](#-api-documentation)
- [Commands Reference](#-artisan-commands)

### 💡 Best Practices

#### Tenant Isolation
```php
// ✅ Always filter by tenant
$projects = Project::where('tenant_id', auth()->user()->current_tenant_id)->get();

// ✅ Or rely on the global scope
$projects = Project::all();  // Already filtered by tenant
```

#### Query Optimization
```php
// ✅ Eager load relationships
$tenants = Tenant::with('users', 'projects')->get();

// ✅ Use select() to limit columns
$tenants = Tenant::select('id', 'name', 'slug')->get();

// ✅ Use pagination
$tenants = Tenant::paginate(15);
```

#### Error Handling
```php
// ✅ Validation
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'slug' => 'required|string|unique:tenants',
]);

// ✅ Try-catch
try {
    $tenant = $this->tenantService->createTenant($dto);
} catch (Exception $e) {
    return back()->with('error', $e->getMessage());
}
```

---

## 📞 Support & Contribution

### 🐛 Report Issues

If you find a bug, please open an issue on GitHub:

1. Describe the problem in detail
2. Provide the stack trace / error message
3. List the steps to reproduce
4. Include environment info (PHP version, Laravel version, etc.)

### 🤝 Contributing

Pull requests are welcome!

```bash
# 1. Fork the repository
# 2. Create a feature branch
git checkout -b feature/amazing-feature

# 3. Commit your changes
git commit -m 'Add some amazing feature'

# 4. Push to the branch
git push origin feature/amazing-feature

# 5. Open a Pull Request
```

### 📧 Contact

- 📩 Email: [your-email@example.com](mailto:your-email@example.com)
- 🐙 GitHub: [yourusername](https://github.com/yourusername)
- 💼 LinkedIn: [Your Name](https://linkedin.com/in/yourname)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE). See the LICENSE file for details.

---

## 🙏 Acknowledgments

- Laravel Community
- Livewire Team
- Spatie (Permission)
- TailwindCSS Team

---

<div align="center">

**Made with ❤️ for the Laravel Community**

⭐ If you find this project useful, please star the repo!

[⬆ Back to top](#-dashboard-sass---multi-tenant-saas-platform)

</div>
