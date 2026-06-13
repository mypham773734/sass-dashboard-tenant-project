<?php 

namespace App\Application\Setup\UseCases; 

use App\Domain\User\Repositories\UserRepositoryInterface; 
use App\Domain\User\Entities\UserEntity; 
use Illuminate\Support\Facades\Hash;
use App\Application\User\DTOs\CreateUserDTO; 
use App\Application\Tenant\DTOs\CreateTenantDTO; 
use Illuminate\Support\Str; 
use App\Domain\User\Enums\RoleEnum;  
use App\Domain\Tenant\Repositories\TenantRepositoryInterface; 
use App\Domain\Tenant\Entities\TenantEntity; 
use App\Application\Tenant\UseCases\SetupDefaultTenantRolesAndPermissionsUseCase;
use App\Models\Role;
use Dom\Entity;

class SetupAppUseCase{
    private array $dataSetupDefault = [
        'user' => [
            'name' => 'jone doe', 
            'email' => 'systemadmin@gmail.com', 
            'password' => 'systemadmin'
        ], 
        'tenant' => [
            'name' => 'Alpha Tech Solutions', 
            'is_active' => true, 
        ]
    ]; 
    public function __construct(
        private readonly UserRepositoryInterface $userRepository, 
        private readonly TenantRepositoryInterface $tenantRepository, 
        private readonly SetupDefaultTenantRolesAndPermissionsUseCase $setupRolePermissionUseCase
    ){}

    public function execute(){
        // Create User
        $user = $this->createUser();  

        // Create Tenant
        $tenant = $this->createTenant();

        // Create Role Default 
        $resultCreateRolePermissionDefault = $this->createRolePermissionDefault($tenant); 

        // Attach user role system admin
        $this->attchUserTenantWithRole($user, $tenant); 
    }


    private function createUser(){
        // Create User
        $name = $this->dataSetupDefault['user']['name'];
        $email = $this->dataSetupDefault['user']['email']; 
        $password = $this->dataSetupDefault['user']['password'];
        $passwordEncrypt = Hash::make($password);  
        
        $createUserDTO = new CreateUserDTO($name, $email, $passwordEncrypt); 
        $user = $this->userRepository->create($createUserDTO); 

        return $user; 
    }

    private function createTenant(){
        $name = $this->dataSetupDefault['tenant']['name'];
        $slug = Str::slug($name); 
        $isAcitve = $this->dataSetupDefault['tenant']['is_active'];
        $entity = new TenantEntity(
            id:          null,
            name:        $name,
            slug:        $slug,
            isActive:    $isAcitve
        );

        return $this->tenantRepository->create($entity);
    }

    private function createRolePermissionDefault(TenantEntity $tenant){
        return $this->setupRolePermissionUseCase->execute($tenant); 
    }

    private function attchUserTenantWithRole(UserEntity $user, TenantEntity $tenant): void
    {
        if ($tenant->id === null || $user->id === null) {
            throw new \DomainException('Tenant or user id is missing.');
        }

        $this->tenantRepository->attachUser($tenant->id, $user->id, RoleEnum::SYSTEM_ADMIN->value);
    }
}