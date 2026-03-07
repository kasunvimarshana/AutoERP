<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantException;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            throw new TenantException('Tenant not found or not specified.', 404);
        }

        if (!$tenant->isActive()) {
            throw new TenantException('Tenant account is inactive or suspended.', 403);
        }

        // Bind to the container so BelongsToTenant trait & services can access it
        app()->instance('current_tenant', $tenant);

        // Inject tenant into request for easy access in controllers
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        // Set tenant-specific runtime configurations
        $this->applyTenantConfigurations($tenant);

        // Add tenant context to response headers
        $response = $next($request);
        $response->headers->set('X-Tenant-ID', (string) $tenant->id);
        $response->headers->set('X-Tenant-Slug', $tenant->slug);

        return $response;
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        $mode = config('tenant.mode', 'header');

        return match ($mode) {
            'subdomain' => $this->resolveFromSubdomain($request),
            'domain'    => $this->resolveFromDomain($request),
            default     => $this->resolveFromHeader($request),
        };
    }

    private function resolveFromHeader(Request $request): ?Tenant
    {
        $header   = config('tenant.header', 'X-Tenant-ID');
        $tenantId = $request->header($header);

        if (!$tenantId) {
            // Fall back to query parameter in development
            $tenantId = $request->query('tenant_id');
        }

        if (!$tenantId) {
            return null;
        }

        return $this->loadTenant(is_numeric($tenantId) ? ['id' => (int) $tenantId] : ['slug' => $tenantId]);
    }

    private function resolveFromSubdomain(Request $request): ?Tenant
    {
        $host   = $request->getHost();
        $suffix = config('tenant.subdomain_suffix', '.example.com');
        $parts  = explode('.', $host);

        if (count($parts) < 2) {
            return null;
        }

        $subdomain = $parts[0];

        return $this->loadTenant(['slug' => $subdomain]);
    }

    private function resolveFromDomain(Request $request): ?Tenant
    {
        $domain = $request->getHost();
        return $this->loadTenant(['domain' => $domain]);
    }

    private function loadTenant(array $conditions): ?Tenant
    {
        $cacheKey = 'tenant:' . implode('_', $conditions);
        $ttl      = config('tenant.cache_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, fn () =>
            Tenant::where($conditions)->first()
        );
    }

    private function applyTenantConfigurations(Tenant $tenant): void
    {
        $ttl = config('tenant.cache_ttl', 3600);

        $configurations = Cache::remember(
            "tenant_config:{$tenant->id}",
            $ttl,
            fn () => $tenant->configurations()->get()
        );

        foreach ($configurations as $config) {
            config(["tenant_runtime.{$config->group}.{$config->key}" => $config->value]);
        }

        // Also expose settings directly
        config(['tenant_runtime.settings' => $tenant->settings ?? []]);
        config(['tenant_runtime.plan'     => $tenant->plan]);
        config(['tenant_runtime.id'       => $tenant->id]);
    }
}
