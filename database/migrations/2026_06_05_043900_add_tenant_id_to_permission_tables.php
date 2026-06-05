<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('guard_name');
            $table->index(['tenant_id', 'name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('guard_name');
            $table->index(['tenant_id', 'name']);
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('model_type');
            $table->index(['model_id', 'model_type', 'tenant_id']);
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('model_type');
            $table->index(['model_id', 'model_type', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex(['model_id', 'model_type', 'tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex(['model_id', 'model_type', 'tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'name']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'name']);
            $table->dropColumn('tenant_id');
        });
    }
};
