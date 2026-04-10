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

    public function index(){
        return view('admin.pages.tenant.index');
    }

    public function store(StoreTenantRequest $request){
        $tenant = $this->tenantService->createTenant($request->validate()); 

        return redirect()->to('dashboard')->with('success', 'Tạo tenant thành công'); 
    }

    public function show(){
        
    }

    public function update(){
        echo "update"; 
    }

    public function destroy(){
        echo "destroy"; 
    }   

    public function create(){
        return view('admin.pages.tenant.create');
    }

    public function edit(){
        echo "edit page"; 
    }
}
