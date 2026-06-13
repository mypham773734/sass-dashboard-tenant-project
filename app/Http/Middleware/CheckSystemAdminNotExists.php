<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Application\Setup\UseCases\IsSystemAdminNotExistsUseCase; 

class CheckSystemAdminNotExists
{

    public function __construct(
        private readonly IsSystemAdminNotExistsUseCase $useCase
    ){}
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!$this->useCase->execute()){
            return $next($request); 
        }

        abort(403, 'System exist system admin');
    }
}
