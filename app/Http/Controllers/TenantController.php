<?php

namespace App\Http\Controllers;

use App\Application\Tenant\DTOs\CreateTenantDTO;
use App\Application\Tenant\DTOs\UpdateTenantDTO;
use App\Application\Tenant\UseCases\CreateTenantUseCase;
use App\Application\Tenant\UseCases\DeleteTenantUseCase;
use App\Application\Tenant\UseCases\FindTenantBySlugUseCase;
use App\Application\Tenant\UseCases\GetTenantsUseCase;
use App\Application\Tenant\UseCases\UpdateTenantUseCase;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    public function __construct(
        private readonly GetTenantsUseCase       $getTenantsUseCase,
        private readonly FindTenantBySlugUseCase $findTenantBySlugUseCase,
        private readonly CreateTenantUseCase     $createTenantUseCase,
        private readonly UpdateTenantUseCase     $updateTenantUseCase,
        private readonly DeleteTenantUseCase     $deleteTenantUseCase,
    ) {}

    public function index()
    {
        try{
            $tenants = $this->getTenantsUseCase->execute(auth()->id());
            
            return view('admin.pages.tenant.index', compact('tenants'));
        }catch(\Exception $e){
            $error = $e; 
            Log::error($e->getMessage()); 
            Log::error($e->getTrace()); 
        }
    }

    public function create()
    {
        return view('admin.pages.tenant.create');
    }

    public function store(StoreTenantRequest $request)
    {
        try {
            $dto    = CreateTenantDTO::fromArray($request->validated());
            $tenant = $this->createTenantUseCase->execute($dto, auth()->id());

            return redirect()
                ->route('tenant.index')
                ->with('success', 'Tenant created successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $tenantSlug)
    {
        $tenant = $this->findTenantBySlugUseCase->execute($tenantSlug, auth()->id());

        if (! $tenant) {
            abort(404);
        }

        return view('admin.pages.tenant.create', ['tenant' => $tenant]);
    }

    public function update(UpdateTenantRequest $request, string $tenantSlug)
    {
        try {
            $dto    = UpdateTenantDTO::fromArray($request->validated());
            $tenant = $this->updateTenantUseCase->execute($tenantSlug, $dto);

            return redirect()
                ->route('tenant.index')
                ->with('success', 'Tenant updated successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $tenantSlug)
    {
        try {
            $this->deleteTenantUseCase->execute(
                slug:            $tenantSlug,
                userId:          auth()->id(),
                currentTenantId: session('current_tenant_id'),
            );

            return redirect()
                ->route('tenant.index')
                ->with('success', 'Tenant deleted successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
