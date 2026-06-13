<?php 

namespace App\Domain\Permission\Repositories; 

use App\Domain\Permission\Entities\PermissionEntity; 

interface PermissionRepositoryInterface{
    public function create(PermissionEntity $permissionEntity): ?PermissionEntity; 
}