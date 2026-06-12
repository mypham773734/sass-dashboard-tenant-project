<?php

namespace App\Http\Controllers\Admin;

use App\Application\Tenant\DTOs\UpdateTenantSettingDTO;
use App\Application\Tenant\UseCases\GetTenantSettingsUseCase;
use App\Application\Tenant\UseCases\UpdateTenantSettingUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantSettingRequest;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TenantSettingController extends Controller
{
    private const SECTIONS = ['email', 'notifications', 'localization', 'members'];

    public function __construct(
        private readonly GetTenantSettingsUseCase $getUseCase,
        private readonly UpdateTenantSettingUseCase $updateUseCase,
    ) {}

    public function index(int $tenantId, string $section = 'email')
    {
        // if (!in_array($section, self::SECTIONS, true)) {
        //     abort(404);
        // }

        try {
            $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
            // $this->authorize('edit', $tenant);

            $settings = $this->getUseCase->execute($tenantId);

            return view("admin.pages.tenant.settings.email", [
                'tenantId' => $tenantId,
                'tenant'   => $tenant,
                'section'  => $section,
                'settings' => $settings,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load settings.');
        }
    }

    public function update(int $tenantId, string $section, UpdateTenantSettingRequest $request)
    {
        if (!in_array($section, self::SECTIONS, true)) {
            abort(404);
        }

        try {
            $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
            $this->authorize('edit', $tenant);

            $dto = UpdateTenantSettingDTO::fromArray($section, $request->validated());
            $this->updateUseCase->execute($tenantId, $dto);

            return redirect()
                ->route('tenant.settings.index', [$tenantId, $section])
                ->with('success', 'Settings updated successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Something went wrong.')->withInput();
        }
    }
}
