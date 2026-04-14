# 🏗️ Dashboard SASS - Multi-Tenant SaaS Platform

<div align="center">

[![Laravel 13](https://img.shields.io/badge/Laravel-13.0+-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![Multi-Tenant](https://img.shields.io/badge/Architecture-Multi--Tenant-40E0D0?style=flat-square)](https://laravel.com)
[![Livewire 4](https://img.shields.io/badge/Livewire-4.x-FB70A9?style=flat-square&logo=livewire)](https://livewire.laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

*Nền tảng SaaS hiện đại với kiến trúc multi-tenant Single Database, hỗ trợ quản lý dự án và người dùng.*

[📖 Documentation](#-tài-liệu) • [🚀 Quick Start](#-cài-đặt-nhanh) • [🏗️ Architecture](#-kiến-trúc) • [📂 API Routes](#-api-documentation) • [🛠️ Troubleshooting](#-troubleshooting)

</div>

---

## 📋 Mục lục

- [🎯 Tổng Quan](#-tổng-quan)
- [✨ Tính Năng Chính](#-tính-năng-chính)
- [🏗️ Kiến Trúc](#-kiến-trúc)
- [📊 Sơ Đồ Luồng Tenant](#-sơ-đồ-luồng-xử-lý-tenant)
- [🚀 Cài Đặt Từ A-Z](#-cài-đặt-từ-a-z)
- [📂 API Documentation](#-api-documentation)
- [⚙️ Các Lệnh Artisan](#-các-lệnh-artisan-số-học)
- [🛠️ Troubleshooting](#-troubleshooting)
- [📚 Tài Liệu Bổ Sung](#-tài-liệu-bổ-sung)

---

## 🎯 Tổng Quan

**Dashboard SASS** là một nền tảng SaaS (Software as a Service) được xây dựng trên Laravel 13 với kiến trúc **Multi-Tenant Single Database**. Ứng dụng cho phép:

✅ Tạo và quản lý nhiều **Tenant** (Công ty/Tổ chức)  
✅ Phân cấp quyền người dùng trong mỗi tenant (Role-based)  
✅ Cô lập dữ liệu một cách an toàn giữa các tenant  
✅ Quản lý dự án (**Projects**) theo tenant  
✅ Giao diện realtime với **Livewire** và **Alpine.js**  
✅ Styling hiện đại với **Tailwind CSS** và **SASS**

### Kiến Trúc Multi-Tenant: Single Database

Thay vì tạo một database riêng cho mỗi tenant (Multi-DB), chúng tôi sử dụng **Single Database** với cột `tenant_id` trong các bảng:

```sql
-- Ví dụ: Bảng Projects
CREATE TABLE projects (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,         -- ← Khóa phân biệt tenant
    owner_id BIGINT NOT NULL,
    name VARCHAR(255),
    description TEXT,
    status VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

**Lợi ích:**
- 💰 Tiết kiệm chi phí server (không cần database riêng)
- 🚀 Dễ dàng backup/restore toàn bộ dữ liệu
- 🔄 Scalability dễ dàng hơn
- 🔐 Global Scope tự động lọc dữ liệu theo user

---

## ✨ Tính Năng Chính

### 🟢 Tenant Management
- ✅ Tạo tenant mới (`POST /admin/tenant`)
- ✅ Liệt kê tenant (`GET /admin/tenant`)
- ✅ Cập nhật tenant (trong `TenantController::update`)
- ✅ Xóa tenant mềm (Soft Delete)
- ✅ Cơ chế Trial Period (trial_ends_at)
- ✅ Settings JSON cho mỗi tenant

### 👥 User & Role Management
- ✅ Tạo người dùng và phân công cho tenant
- ✅ Role-based access (admin, member, viewer)
- ✅ Gán role qua pivot table `tenant_user` với cột `role`
- ✅ Hỗ trợ Spatie Laravel Permission

### 📁 Project Management
- ✅ Tạo project thuộc tenant
- ✅ Ghi owner_id (người tạo)
- ✅ Trạng thái project (active, archived, etc.)
- ✅ Mô tả & metadata project
- ✅ Soft delete projects

### 🔐 Multi-Tenancy Features
- ✅ **Global Scope**: Tự động lọc query theo user
- ✅ **Tenant Isolation**: Dữ liệu được cô lập an toàn
- ✅ **Automatic Filtering**: Eloquent query tự động chỉ lấy data của tenant hiện tại
- ✅ **Permission Integration**: Kết hợp Spatie Permission

### 🎨 Frontend
- ✅ Giao diện Admin Dashboard
- ✅ Blade Templates + Livewire Components
- ✅ Tailwind CSS + SASS
- ✅ Alpine.js for interactivity
- ✅ Custom Auth UI

### 🔧 Backend Architecture
- ✅ **Service Layer Pattern**: `TenantService` implement interface
- ✅ **Repository Pattern**: Contracts + Implementations
- ✅ **DTO Pattern**: `CreateTenantDTO` for data transfer
- ✅ **Dependency Injection**: Binding tại `AppServiceProvider`
- ✅ **Custom Request Validation**: `StoreTenantRequest`

---

## 🏗️ Kiến Trúc

### Cấu Trúc Thư Mục

```
dashboard-sass/
├── app/
│   ├── DTOs/                          # Data Transfer Objects
│   │   └── tenants/
│   │       └── CreateTenantDTO.php    # DTO cho tạo tenant
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── TenantController.php   # Quản lý tenant
│   │   │   ├── ProfileController.php
│   │   │   └── CustomAuth/
│   │   │       └── AuthenticatedSessionController.php
│   │   └── Requests/
│   │       └── StoreTenantRequest.php # Validation request
│   │
│   ├── Models/
│   │   ├── Tenant.php                 # Model Tenant (main)
│   │   ├── User.php                   # Model User
│   │   ├── Project.php                # Model Project
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
│   ├── Traits/                         # Reusable traits
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
    tenant_id BIGINT NOT NULL,       -- ← Khóa phân biệt tenant
    owner_id BIGINT NOT NULL,        -- ← Người tạo
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

## 📊 Sơ Đồ Luồng Xử Lý Tenant

```mermaid
graph TD
    A["🌐 User Request<br/>GET /admin/tenant"] --> B["🔐 Middleware: auth<br/>Xác thực user"]
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

### Luồng Chi Tiết:

#### 1️⃣ **Request Phase**
```php
// User yêu cầu: GET /admin/tenant
Route::middleware('auth')->group(function () {
    Route::resource('/tenant', TenantController::class);
});
```

#### 2️⃣ **Controller Phase**
```php
public function index()
{
    $tenants = Tenant::all();  // 🔑 Áp dụng TenantScope
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
        // Chỉ lấy tenant mà user hiện tại thuộc về
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
// View nhận mảng tenants đã lọc
// Chỉ hiển thị tenant của user hiện tại
```

---

## 🚀 Cài Đặt Từ A-Z

### 📋 Yêu Cầu Hệ Thống

- **PHP**: 8.3 trở lên
- **Composer**: Phiên bản mới nhất
- **Node.js**: 16+ (cho Vite & npm)
- **Database**: SQLite (mặc định) hoặc MySQL 8.0+ / PostgreSQL 12+
- **Git**: Để clone repository

### ⚡ Quick Start (5 phút)

```bash
# 1. Clone repository
git clone https://github.com/yourusername/dashboard-sass.git
cd dashboard-sass

# 2. Chạy setup script tự động
composer run setup

# ✅ Setup hoàn thành! Ứng dụng sẵn sàng
```

### 📖 Chi Tiết Từng Bước

#### **Bước 1: Chuẩn Bị Môi Trường**

```bash
# 1.1 Clone repository
git clone https://github.com/yourusername/dashboard-sass.git
cd dashboard-sass

# 1.2 Copy .env.example thành .env
cp .env.example .env

# 1.3 Hoặc edit .env cho cơ sở dữ liệu (tùy chọn)
# SQLite (Mặc định):
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

#### **Bước 2: Cài Đặt Dependencies**

```bash
# 2.1 Cài PHP dependencies
composer install

# 2.2 Generate application key
php artisan key:generate

# 2.3 Cài Node.js dependencies
npm install --ignore-scripts

# Hoặc nếu dùng pnpm/yarn
pnpm install
# hay
yarn install
```

#### **Bước 3: Cấu Hình Database**

```bash
# 3.1 Tạo database (nếu dùng MySQL/PostgreSQL)
# MySQL:
mysql -u root -p -e "CREATE DATABASE dashboard_sass;"

# PostgreSQL:
createdb dashboard_sass

# 3.2 Chạy migrations
php artisan migrate

# 3.3 (Tùy chọn) Chạy seeders
php artisan db:seed
```

#### **Bước 4: Xây Dựng Frontend Assets**

```bash
# 4.1 Dev mode (watch cho changes)
npm run dev

# Hoặc build production
npm run build
```

#### **Bước 5: Chạy Ứng Dụng**

```bash
# Option 1: PHP built-in server
php artisan serve

# Output: Starting Laravel development server: http://127.0.0.1:8000

# Option 2: Chạy đầy đủ (server + queue + logs + frontend dev)
composer run dev

# Output:
# ✓ server  | Laravel development server running
# ✓ queue   | Processing jobs...
# ✓ logs    | Listening to logs...
# ✓ vite    | Vite starting...
```

#### **Bước 6: Truy Cập Ứng Dụng**

```
🌐 Mở browser và truy cập:
   http://localhost:8000

📝 Đăng ký tài khoản hoặc đăng nhập
💼 Tạo tenant đầu tiên từ /admin/tenant
✅ Bắt đầu quản lý project!
```

### 🐳 Cài Đặt Với Docker (Tùy Chọn)

```bash
# 1. Clone repository
git clone https://github.com/yourusername/dashboard-sass.git
cd dashboard-sass

# 2. Sao chép .env
cp .env.example .env

# 3. Xây dựng Docker image
docker-compose build

# 4. Khởi động containers
docker-compose up -d

# 5. Chạy commands trong container
docker-compose exec app composer install
docker-compose exec app php artisan migrate
docker-compose exec app npm install
docker-compose exec app npm run build

# 6. Truy cập ứng dụng
# http://localhost:80
```

#### `docker-compose.yml` (Tạo file này)

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

#### 📋 Liệt Kê Tenants
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

#### ✨ Form Tạo Tenant
```http
GET /admin/tenant/create
Authorization: Authenticated

Response: 200 OK
Content-Type: text/html

HTML: admin.pages.tenant.create form
```

---

#### ➕ Tạo Tenant Mới
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
  "role": "admin",  // ← Có thể là: admin, member, viewer
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

Tất cả POST/PATCH/DELETE requests cần CSRF token:

```html
<!-- Blade Template -->
<form method="POST" action="/admin/tenant">
    @csrf
    <input type="text" name="name" required>
    <button type="submit">Create Tenant</button>
</form>
```

```php
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

## ⚙️ Các Lệnh Artisan Số Học

### 🚀 Development

```bash
# Chạy development server (PHP built-in)
php artisan serve
# Output: http://127.0.0.1:8000

# Chạy tất cả (server + queue + logs + frontend dev)
composer run dev

# Chạy Tinker (Interactive Shell)
php artisan tinker

# Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 📊 Database

```bash
# Chạy tất cả migrations
php artisan migrate

# Rollback migration cuối cùng
php artisan migrate:rollback

# Rollback tất cả migrations
php artisan migrate:reset

# Refresh databases (reset + migrate)
php artisan migrate:refresh

# Seed database
php artisan db:seed

# Refresh + Seed
php artisan migrate:refresh --seed

# Kiểm tra migration status
php artisan migrate:status
```

### 🛠️ Tenant Management

```bash
# Lệnh tùy chỉnh (nếu tạo command)
php artisan tenant:create --name="New Company" --slug="new-company"

# Liệt kê all tenants
php artisan tinker
>>> Tenant::all();

# Lấy tenant cụ thể
>>> Tenant::where('slug', 'acme-corp')->first();

# Thêm user vào tenant
>>> $tenant = Tenant::find(1);
>>> $tenant->users()->attach(2, ['role' => 'admin']);
```

### ✉️ Mail

```bash
# Test mail configuration
php artisan mail:show

# Kiểm tra mail queue
php artisan queue:listen --tries=1 --timeout=0
```

### 🧪 Testing

```bash
# Chạy tất cả tests
php artisan test

# Chạy tests với coverage
php artisan test --coverage

# Chạy test file cụ thể
php artisan test tests/Feature/Auth/LoginTest.php

# Chạy unit tests chỉ
php artisan test tests/Unit/

# Chạy feature tests chỉ
php artisan test tests/Feature/
```

### 📋 Code Quality

```bash
# Lint PHP code
./vendor/bin/pint

# Lint + Fix automatically
./vendor/bin/pint --fix

# Check code with PHPStan (nếu cài)
php artisan ide-helper:generate

# Xem routes
php artisan route:list

# Xem models
php artisan model:show
```

### 🔍 Debugging

```bash
# Monitor logs real-time
php artisan pail

# Xem SQL queries
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
# Xóa old logs
php artisan logs:clear

# Xóa failed jobs
php artisan queue:failed-table
php artisan queue:flush

# Reset queue
php artisan queue:restart
```

---

## 🛠️ Troubleshooting

### 🔴 Lỗi Tenant Isolation

#### ❌ Lỗi: User thấy data của tenant khác

**Nguyên nhân:**
- Global Scope chưa được áp dụng
- TenantScope::apply() không được gọi
- User chưa được authenticate

**Giải pháp:**

```php
// In Model:
public static function booted()
{
    static::addGlobalScope(new TenantScope);  // ✅ Đảm bảo có
}

// Kiểm tra TenantScope:
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
>>> auth()->user();  // Kiểm tra nếu user được authenticate
>>> Tenant::all();   // Kiểm tra nếu scope được áp dụng
```

#### ❌ Lỗi: Certain models không lọc theo tenant

**Nguyên nhân:**
- Model không có TenantScope global scope
- Model không có quan hệ belongsTo(Tenant::class)

**Giải pháp:**

```php
// ❌ Sai (không có scope)
class Project extends Model
{
    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}

// ✅ Đúng (có scope)
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

### 🔴 Lỗi Migration

#### ❌ Lỗi: "SQLSTATE[HY000]: General error: 1 Error while executing query"

**Nguyên nhân:**
- SQLite không hỗ trợ foreign key theo mặc định
- Hoặc bảng chưa tồn tại

**Giải pháp:**

```php
// In migration file:
Schema::enableForeignKeyConstraints();  // ← Enable trước khi migrate

Schema::create('projects', function (Blueprint $table) {
    // ... columns ...
});

Schema::disableForeignKeyConstraints();

// Hoặc trong config/database.php:
'sqlite' => [
    'driver' => 'sqlite',
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => true,  // ← Thêm dòng này
],
```

#### ❌ Lỗi: "Table already exists"

**Giải pháp:**

```bash
# Reset database
php artisan migrate:reset

# Hoặc xóa database file (SQLite)
rm database/database.sqlite
php artisan migrate
```

---

### 🔴 Lỗi Permission/Role

#### ❌ Lỗi: User không có role trong tenant

**Nguyên nhân:**
- User chưa được gán vào tenant_user table
- Role không được set đúng

**Giải pháp:**

```php
// ✅ Gán user vào tenant khi tạo
$tenant = Tenant::create($data);
$tenant->users()->attach(auth()->id(), ['role' => 'admin']);

// ✅ Kiểm tra user có trong tenant
$user = User::find(1);
$user->tenants;  // Danh sách tenants của user

// ✅ Kiểm tra role
$tenant = Tenant::find(1);
$tenant->users()->where('user_id', auth()->id())->first();
// Output: User object với pivot.role
```

---

### 🔴 Lỗi Setting của Tenant

#### ❌ Lỗi: Settings JSON không hoạt động

**Giải pháp:**

```php
// ✅ Khai báo casts trong model
class Tenant extends Model
{
    protected $casts = [
        'settings' => 'array',  // ← Tự động convert JSON
    ];
}

// ✅ Sử dụng settings
$tenant = Tenant::find(1);
$tenant->settings['theme'] = 'dark';
$tenant->save();

// ✅ Query settings
Tenant::where('settings->theme', 'dark')->get();
```

---

### 🔴 Lỗi Trial Period

#### ❌ Lỗi: Tenant subscription hết hạn

**Giải pháp:**

```php
// ✅ Kiểm tra trial status
$tenant = Tenant::find(1);
if ($tenant->trial_ends_at && $tenant->trial_ends_at < now()) {
    // Tenant trial đã hết
    return response()->json(['message' => 'Trial expired'], 403);
}

// ✅ Trong scope
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if(auth()->check()) {
            $builder->whereHas('users', function ($q) {
                $q->where('users.id', auth()->id());
            })->where('is_active', true);  // ← Chỉ active tenants
            // ->where('trial_ends_at', '>', now());  // ← Optional
        }
    }
}
```

---

### 🔴 Lỗi Performance / N+1 Queries

#### ❌ Lỗi: Queries quá nhiều khi load tenants

**Nguyên nhân:**
- Không dùng eager loading
- Global Scope gây N+1 queries

**Giải pháp:**

```php
// ❌ Sai (N+1 queries)
$tenants = Tenant::all();
foreach ($tenants as $tenant) {
    echo $tenant->users()->count();  // ← Query cho mỗi tenant
}

// ✅ Đúng (eager load)
$tenants = Tenant::with('users', 'projects')->get();
foreach ($tenants as $tenant) {
    echo count($tenant->users);  // ← Không query thêm
}

// ✅ Debug queries
DB::listen(function ($query) {
    \Log::info($query->sql, $query->bindings);
});
```

---

### 🔴 Lỗi View/Blade

#### ❌ Lỗi: View không found

**Nguyên nhân:**
- Path view sai
- View file không tồn tại

**Giải pháp:**

```php
// ✅ Kiểm tra path
// resources/views/admin/pages/tenant/index.blade.php
// Tương ứng: admin.pages.tenant.index

// ✅ Debug
php artisan view:clear
php artisan config:clear
php artisan cache:clear

// ✅ Cách gọi:
return view('admin.pages.tenant.index', [
    'tenants' => $tenants
]);
```

---

### 🔴 Lỗi Assets (CSS/JS)

#### ❌ Lỗi: CSS/JS không load

**Nguyên nhân:**
- Vite dev server chưa chạy
- Build chưa được chạy

**Giải pháp:**

```bash
# Dev mode (watch)
npm run dev

# Hoặc build production
npm run build

# Kiểm tra manifest
ls public/build/manifest.json

# Clear cache
php artisan cache:clear
```

---

## 📚 Tài Liệu Bổ Sung

### 🔗 Tài Liệu Chính Thức

- 📖 [Laravel Documentation](https://laravel.com/docs/13.x)
- 📘 [Livewire Documentation](https://livewire.laravel.com)
- 🎨 [Tailwind CSS](https://tailwindcss.com)
- ⚙️ [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)

### 📁 Project Files

- [Architecture Diagram](#-kiến-trúc)
- [Database Schema](#database-schema)
- [API Routes](#-api-documentation)
- [Commands Reference](#-các-lệnh-artisan-số-học)

### 💡 Best Practices

#### Tenant Isolation
```php
// ✅ Luôn filter theo tenant
$projects = Project::where('tenant_id', auth()->user()->current_tenant_id)->get();

// ✅ Hoặc dùng global scope
$projects = Project::all();  // Đã filtered by tenant
```

#### Query Optimization
```php
// ✅ Eager load relationships
$tenants = Tenant::with('users', 'projects')->get();

// ✅ Use select() to limit columns
$tenants = Tenant::select('id', 'name', 'slug')->get();

// ✅ Pagination
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

Nếu bạn tìm thấy bug, vui lòng tạo issue trên GitHub:

1. Mô tả vấn đề chi tiết
2. Cung cấp stack trace/error message
3. Các bước để reproduce
4. Environment info (PHP version, Laravel version, etc.)

### 🤝 Contributing

Chúng tôi chào đón pull requests!

```bash
# 1. Fork repository
# 2. Tạo feature branch
git checkout -b feature/amazing-feature

# 3. Commit changes
git commit -m 'Add some amazing feature'

# 4. Push to branch
git push origin feature/amazing-feature

# 5. Open a Pull Request
```

### 📧 Contact

- 📩 Email: [your-email@example.com](mailto:your-email@example.com)
- 🐙 GitHub: [yourusername](https://github.com/yourusername)
- 💼 LinkedIn: [Your Name](https://linkedin.com/in/yourname)

---

## 📄 License

Dự án này được cấp phép dưới [MIT License](LICENSE). Xem file LICENSE để chi tiết.

---

## 🙏 Acknowledgments

- Laravel Community
- Livewire Team
- Spatie (Permission)
- TailwindCSS Team

---

<div align="center">

**Made with ❤️ for the Laravel Community**

⭐ Nếu bạn thích dự án này, vui lòng star repo!

[⬆ Back to top](#-dashboard-sass---multi-tenant-saas-platform)

</div>
