<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Application\Tenant\UseCases\{
    CreateTenantUseCase, 
    DeleteTenantUseCase, 
    FindTenantBySlugUseCase, 
    GetTenantsUseCase, 
    UpdateTenantUseCase
}; 
use App\Application\User\UseCases\ChangeTenantSelectedUseCase;
use App\Http\Requests\Tenant\{
    UpdateTenantRequest, 
    StoreTenantRequest
};
use App\DTOs\Tenants\CreateTenantDTO; 
use App\Application\Tenant\DTOs\UpdateTenantDTO; 

class TenantController extends Controller
{
    public function __construct(
        private readonly GetTenantsUseCase          $getTenantsUseCase,
        private readonly FindTenantBySlugUseCase    $findTenantBySlugUseCase,
        private readonly CreateTenantUseCase        $createTenantUseCase,
        private readonly UpdateTenantUseCase        $updateTenantUseCase,
        private readonly DeleteTenantUseCase        $deleteTenantUseCase,
        private readonly ChangeTenantSelectedUseCase $changeTenantSelectedUseCase,
    ) {}

    public function index()
    {
        try {
            $userId = authContext()->getId(); 
            $tenants = $this->getTenantsUseCase->execute($userId);

            return view('admin.pages.tenant.index', compact('tenants'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load tenants.');
        }
    }

    public function create()
    {
        try {
            return view('admin.pages.tenant.create');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function store(StoreTenantRequest $request)
    {
        try {
            $dto = CreateTenantDTO::fromArray($request->validated());
            $userId = authContext()->getId(); 
            $this->createTenantUseCase->execute($dto, $userId);

            return redirect()
                ->route('tenant.index')
                ->with('success', 'Tenant created successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to create tenant.')->withInput();
        }
    }

    public function edit(string $tenantSlug)
    {
        try {
            $userId = authContext()->getId(); 
            $tenant = $this->findTenantBySlugUseCase->execute($tenantSlug, $userId);

            if (! $tenant) {
                abort(404);
            }

            return view('admin.pages.tenant.create', ['tenant' => $tenant]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load tenant.');
        }
    }

    public function update(UpdateTenantRequest $request, string $tenantSlug)
    {
        try {
            $dto = UpdateTenantDTO::fromArray($request->validated());
            $this->updateTenantUseCase->execute($tenantSlug, $dto);

            return redirect()
                ->route('tenant.index')
                ->with('success', 'Tenant updated successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to update tenant.')->withInput();
        }
    }

    public function switchTenant(int $tenantId)
    {
        try {
            $userId = authContext()->getId(); 
            $this->changeTenantSelectedUseCase->execute($userId, $tenantId);

            tenantContext()->setId($tenantId);

            return redirect()->route('dashboard')->with('success', 'Workspace switched.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to switch workspace.');
        }
    }

    public function destroy(string $tenantSlug)
    {
        try {
            $tenantId = tenantContext()->getId(); 
            $userId = authContext()->getId(); 

            $this->deleteTenantUseCase->execute(
                slug:            $tenantSlug,
                userId:          $userId,
                currentTenantId: $tenantId,
            );

            return redirect()
                ->route('tenant.index')
                ->with('success', 'Tenant deleted successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to delete tenant.');
        }
    }
}
