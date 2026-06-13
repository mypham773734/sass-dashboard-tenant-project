<?php 

namespace App\Domain\Permission\Entities; 

class PermissionEntity{
    public function __construct(
        public readonly ?int $id, 
        public readonly string $name, 
        public readonly string $guardName, 
        public readonly ?int $tenantId, 
    ){}
}