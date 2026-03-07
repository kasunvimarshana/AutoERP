<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantConfigService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(private readonly TenantConfigService $tenantConfigService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if (! $tenantId) {
            return $this->tenantMissingResponse();
        }

        $tenant = Tenant::where('id', $tenantId)->where('is_active', true)->first();

        if (! $tenant) {
            return $this->tenantNotFoundResponse($tenantId);
        }

        // Bind tenant to the IoC container so any class can resolve it
        App::instance('current_tenant', $tenant);

        // Store on the request for easy access in controllers
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        // Apply tenant-specific mail settings at runtime
        try {
            $this->tenantConfigService->applyMailConfig($tenant->id);
        } catch (\Throwable $e) {
            Log::warning('Failed to apply tenant mail config', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
        }

        Log::debug('Tenant resolved', ['tenant_id' => $tenant->id, 'domain' => $tenant->domain]);

        return $next($request);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveTenantId(Request $request): ?string
    {
        // 1. Header (preferred for API clients)
        $headerName = config('tenant.header', 'X-Tenant-ID');
        if ($id = $request->header($headerName)) {
            return (string) $id;
        }

        // 2. Authenticated user's tenant
        if ($user = $request->user()) {
            return (string) $user->tenant_id;
        }

        // 3. Domain-based resolution (optional convenience)
        $host   = $request->getHost();
        $tenant = Tenant::where('domain', $host)->where('is_active', true)->first();
        if ($tenant) {
            return (string) $tenant->id;
        }

        return null;
    }

    private function tenantMissingResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tenant identification required. Provide the X-Tenant-ID header.',
        ], 400);
    }

    private function tenantNotFoundResponse(string $tenantId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "Tenant {$tenantId} not found or inactive.",
        ], 404);
    }
}
