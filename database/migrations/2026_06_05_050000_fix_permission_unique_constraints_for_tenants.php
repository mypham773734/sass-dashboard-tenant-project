<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop Spatie's default global unique index, replace with tenant-scoped one
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['name', 'guard_name', 'tenant_id'], 'permissions_name_guard_tenant_unique');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['name', 'guard_name', 'tenant_id'], 'roles_name_guard_tenant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_tenant_unique');
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_name_guard_tenant_unique');
            $table->unique(['name', 'guard_name']);
        });
    }
};
