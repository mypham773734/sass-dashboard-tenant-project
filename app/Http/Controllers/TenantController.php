<?php

namespace App\Http\Controllers;

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
        $tenants = Tenant::paginate(10); 

        $data = $tenants; 
        return view('admin.pages.tenant.index', compact('tenants'));
    }

    public function store(StoreTenantRequest $request){
        try {
            $dto = CreateTenantDTO::fromArray($request->all());
            $tenant = $this->tenantService->createTenant($dto);

            $user = $request->user(); 

            $tenant->users()->attach($user->id, ['role' => 'admin']); 

            return redirect()->route('tenant.index')->with('success', 'Tạo tenant thành công');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi tạo tenant: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(){
        
    }

    public function update(string $tenantSlug){
        try {
            $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail(); 

            $dto = CreateTenantDTO::fromArray(request()->all());
            $tenant = $this->tenantService->updateTenant($tenant, $dto);

            return view('admin.pages.tenant.create', compact('tenant'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi cập nhật tenant: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(string $tenantSlug){
        try {
            $userId = Auth()->id;
            $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail(); 

            $selectedCurrentTenantId = session('current_tenant_id'); 
            if($selectedCurrentTenantId === $tenant->id){
                return redirect()->back()
                    ->with('error', 'Bạn không thể xóa tenant đang chọn làm tenant hiện tại');
            }           

            $result = $this->tenantService->deleteTenant($tenant, $userId);

            return redirect()->back()->with('success', 'Xóa tenant thành công');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi xóa tenant: ' . $e->getMessage());
        }
    }

    public function create(){
        return view('admin.pages.tenant.create');
    }

    public function edit(string $tenantSlug){
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail(); 

        return view('admin.pages.tenant.create', compact('tenant'));
    }
}
