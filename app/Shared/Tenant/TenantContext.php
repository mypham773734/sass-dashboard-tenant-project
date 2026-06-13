<?php 

namespace App\Shared\Tenant; 

use App\Domain\Tenant\Repositories\TenantRepositoryInterface; 

class TenantContext{
    private const string SESSION_KEY  = 'current_tenant_id'; 

    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository
    ){}

    public function getId():int{
        return session(self::SESSION_KEY); 
    }

    public function setId(int $tenantId):void{
        session()->put(self::SESSION_KEY, $tenantId);
    }

    public function forget():void{
        session()->forget(self::SESSION_KEY); 
    }

    public function has(){
        return session()->has(self::SESSION_KEY);
    }

    public function getTenant(){
        $tenantId = $this->getId(); 

        return $this->tenantRepository->findById($tenantId); 
    }
}