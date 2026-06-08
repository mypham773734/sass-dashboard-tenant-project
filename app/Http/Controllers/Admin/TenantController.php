<?php

namespace App\Http\Controllers\Admin;

use App\Application\Tenant\UseCases\CreateTenantUseCase;
use App\Application\Tenant\UseCases\DeleteTenantUseCase;
use App\Application\Tenant\UseCases\FindTenantBySlugUseCase;
use App\Application\Tenant\UseCases\GetTenantsUseCase;
use App\Application\Tenant\UseCases\UpdateTenantUseCase;
use App\Application\User\UseCases\ChangeTenantSelectedUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use Illuminate\Support\Facades\Log;
use App\Shared\Tenant\TenantContext; 
use App\DTOs\Tenants\CreateTenantDTO; 

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
            $tenants = $this->getTenantsUseCase->execute(auth()->id());

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
            $this->createTenantUseCase->execute($dto, auth()->id());

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
            $tenant = $this->findTenantBySlugUseCase->execute($tenantSlug, auth()->id());

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
            $userId = auth()->id(); 
            $this->changeTenantSelectedUseCase->execute($userId, $tenantId);

            app(TenantContext::class)->setId($tenantId);

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
            $tenantId = app(TenantContext::class)->getId(); 
            $userId = auth()->id(); 

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
