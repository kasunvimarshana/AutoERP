<?php

namespace App\Http\Middleware;

use App\Domain\Models\Tenant;
use App\Infrastructure\Tenant\TenantDatabaseManager;
use App\Infrastructure\Tenant\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly TenantDatabaseManager $dbManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or could not be resolved.',
                'error'   => 'TENANT_NOT_RESOLVED',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Tenant account is suspended or inactive.',
                'error'   => 'TENANT_INACTIVE',
            ], Response::HTTP_FORBIDDEN);
        }

        // Set tenant on the request for downstream use
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        // Switch database connection to this tenant's schema/database
        $this->dbManager->connectForTenant($tenant);

        // Bind the tenant into the IoC container
        app()->instance(Tenant::class, $tenant);
        app()->instance('current_tenant', $tenant);

        Log::withContext(['tenant_id' => $tenant->id, 'tenant_slug' => $tenant->subdomain]);

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        $identifier = $this->resolver->extractIdentifier($request);

        if ($identifier === null) {
            return null;
        }

        return Cache::remember($identifier, config('tenant.cache_ttl', 3600), function () use ($request) {
            return $this->resolver->resolve($request);
        });
    }
}
