<?php 

namespace App\Application\Tenant\UseCases; 

use App\Domain\Tenant\Repositories\TenantRepositoryInterface; 

class GetAllTenantsUseCase{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository
    ){}

    public function execute(int $userId){
        return $this->tenantRepository->findAllByUserId($userId); 
    }
}