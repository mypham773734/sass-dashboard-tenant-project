<?php

namespace App\Http\Middleware;

use Closure;
use ErrorException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChooseCurrentTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = tenantContext()->getId(); 
        if(!$tenantId){   
            abort(403, 'Not Found Current Tenan');
        }

        return $next($request); 
    }
}
