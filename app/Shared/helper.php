<?php 

use App\Shared\Tenant\TenantContext; 
use App\Shared\Auth\AuthContext; 

if(!function_exists('authContext')){
    function authContext(){
        return app(AuthContext::class);
    }
}

if(!function_exists('tenantContext')){
    function tenantContext(){
        return app(TenantContext::class); 
    }
}