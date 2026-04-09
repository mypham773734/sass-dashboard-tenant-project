<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\Contracts\TenantServiceInterface; 
use App\Http\Requests\StoreTenantRequest; 

class TenantController extends Controller
{
    protected $tenantService; 

    public function __construct(TenantServiceInterface $tenantService)
    {
        $this->tenantService = $tenantService; 
    }

    public function store(StoreTenantRequest $request){
        $tenant = $this->tenantService->createTenant($request->validate()); 

        return redirect()->to('dashboard')->with('success', 'Tạo tenant thành công'); 
    }
}
