<?php
namespace App\Middleware;

use App\Models\Tenant;
use App\Services\TenantConfigService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class TenantMiddleware
{
    public function __construct(private TenantConfigService $tenantConfigService) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        if (!$tenant->is_active) {
            return response()->json(['message' => 'Tenant is not active'], 403);
        }

        App::instance('tenant', $tenant);
        $this->tenantConfigService->applyConfigs($tenant);

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            return Tenant::where('domain', $subdomain)->first();
        }

        $tenantId = $request->query('tenant_id');
        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        return null;
    }
}
