<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Tenant\TenantContext; 

class SetDefaultTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !app(TenantContext::class)->has()) {
            $firstTenant = auth()->user()->tenants()->first();

            if ($firstTenant) {
                $tenantId = $firstTenant->id;
                
                app(TenantContext::class)->setId($tenantId);
            }
        }

        return $next($request);
    }
}
