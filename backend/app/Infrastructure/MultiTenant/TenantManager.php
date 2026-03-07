<?php

declare(strict_types=1);

namespace App\Infrastructure\MultiTenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TenantManager — holds and manages the current tenant context for a request.
 *
 * Registered as a singleton in the service container under the key
 * `tenant.manager`. Middleware resolves the tenant and calls `setCurrentTenant()`.
 */
class TenantManager
{
    private ?Tenant $currentTenant = null;

    /**
     * Set the active tenant for the current request lifecycle.
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;

        // Make tenant config available as runtime config.
        foreach ($tenant->config ?? [] as $key => $value) {
            config(["tenant.runtime.{$key}" => $value]);
        }

        Log::debug("[TenantManager] Active tenant: {$tenant->slug} (#{$tenant->id})");
    }

    /**
     * Return the active tenant, or null when outside a tenant context.
     */
    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Return the current tenant's id, or null.
     */
    public function getCurrentTenantId(): int|string|null
    {
        return $this->currentTenant?->id;
    }

    /**
     * Return true when a tenant context has been established.
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Resolve and cache a tenant by id.
     */
    public function resolveTenantById(int|string $id): ?Tenant
    {
        return Cache::remember(
            "tenant:{$id}",
            now()->addMinutes(10),
            fn () => Tenant::find($id)
        );
    }

    /**
     * Resolve and cache a tenant by slug.
     */
    public function resolveTenantBySlug(string $slug): ?Tenant
    {
        return Cache::remember(
            "tenant:slug:{$slug}",
            now()->addMinutes(10),
            fn () => Tenant::where('slug', $slug)->first()
        );
    }

    /**
     * Resolve and cache a tenant by domain.
     */
    public function resolveTenantByDomain(string $domain): ?Tenant
    {
        return Cache::remember(
            "tenant:domain:{$domain}",
            now()->addMinutes(10),
            fn () => Tenant::where('domain', $domain)->first()
        );
    }

    /**
     * Flush the cached tenant data (called after config updates).
     */
    public function flushTenantCache(int|string $tenantId): void
    {
        $tenant = $this->resolveTenantById($tenantId);

        Cache::forget("tenant:{$tenantId}");

        if ($tenant) {
            Cache::forget("tenant:slug:{$tenant->slug}");
            Cache::forget("tenant:domain:{$tenant->domain}");
        }
    }

    /**
     * Clear the current in-memory tenant context (useful after tenant switches).
     */
    public function clearCurrentTenant(): void
    {
        $this->currentTenant = null;
    }
}
