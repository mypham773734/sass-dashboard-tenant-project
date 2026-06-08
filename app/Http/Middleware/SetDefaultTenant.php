<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !tenantContext()->has()) {
            $firstTenant = auth()->user()->tenants()->first();

            if ($firstTenant) {
                $tenantId = $firstTenant->id;
                
                tenantContext()->setId($tenantId);
            }
        }

        return $next($request);
    }
}
