<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use App\Application\Tenant\UseCases\SetupDefaultTenantRolesAndPermissionsUseCase; 

class RolePermissionSeeder extends Seeder
{
    public function __construct(
        private readonly SetupDefaultTenantRolesAndPermissionsUseCase $setupRolePermissionUseCase
    ){}
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {
            $this->setupRolePermissionUseCase->execute($tenant->id); 

            $this->command->info("Created roles & permissions for tenant: {$tenant->name}");
        }
    }
}
