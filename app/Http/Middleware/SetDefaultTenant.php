<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (authContext()->checkLogin() && !tenantContext()->has()) {
            $userLogin = authContext()->getUser(); 
            $firstTenant = $userLogin->tenants()->first();

            if ($firstTenant) {
                $tenantId = $firstTenant->id;
                
                tenantContext()->setId($tenantId);
            }
        }

        return $next($request);
    }
}
