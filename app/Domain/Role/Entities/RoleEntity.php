<?php 

namespace App\Domain\Role\Entities; 

class RoleEntity{
    public function __construct(
        public readonly int $id, 
        public readonly string $name, 
        public readonly string $guardName, 
        public readonly ?int $tenantId, 
    ){}
}