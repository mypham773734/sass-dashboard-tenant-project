<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! session()->has('current_tenant_id')) {
            $firstTenant = auth()->user()->tenants()->first();

            if ($firstTenant) {
                session()->put('current_tenant_id', $firstTenant->id);
            }
        }

        return $next($request);
    }
}
