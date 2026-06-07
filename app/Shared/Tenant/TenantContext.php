<?php 

namespace App\Shared\Tenant; 

class TenantContext{
    private const string SESSION_KEY  = 'current_tenant_id'; 

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
}