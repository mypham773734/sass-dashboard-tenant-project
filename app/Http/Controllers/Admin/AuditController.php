<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Application\Audit\UseCases\GetAuditLogsUseCase;
use App\Http\Controllers\Controller;
use App\Models\Tenant;

class AuditController extends Controller
{
    public function __construct(
        private readonly GetAuditLogsUseCase $getAuditLogsUseCase,
    ) {}

    public function index(Request $request)
    {
        try {
            $tenantId = tenantContext()->getId();
            $tenant   = Tenant::findOrFail($tenantId);

            $this->authorize('viewAuditLog', $tenant);

            $filters = $request->only(['user_id', 'action', 'from', 'to']);
            $logs    = $this->getAuditLogsUseCase->execute($tenantId, $filters);

            return view('admin.pages.audit.index', compact('logs', 'filters'));
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load audit logs.');
        }
    }
}
