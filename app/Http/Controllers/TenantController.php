<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\Contracts\TenantServiceInterface; 
use App\Http\Requests\StoreTenantRequest; 
use App\DTOs\Tenants\CreateTenantDTO;
use App\Models\Tenant;

class TenantController extends Controller
{
    protected $tenantService; 

    public function __construct(TenantServiceInterface $tenantService)
    {
        $this->tenantService = $tenantService; 
    }

    public function index(){
        $tenants = Tenant::all(); 
        return view('admin.pages.tenant.index', [
            'tenants' => $tenants
        ]);
    }

    public function store(StoreTenantRequest $request){
        try {
            $dto = CreateTenantDTO::fromArray($request->all());
            $tenant = $this->tenantService->createTenant($dto);

            $user = $request->user(); 

            $tenant->users()->attach($user->id, ['role' => 'admin']); 

            return redirect()->back()->with('success', 'Tạo tenant thành công');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi tạo tenant: ' . $e->getMessage())
                ->withInput();
        }
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
