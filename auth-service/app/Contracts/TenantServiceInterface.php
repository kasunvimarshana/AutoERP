<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contract for multi-tenant management operations.
 *
 * Separates tenant lifecycle management from authentication
 * concerns, making it easy to swap tenant resolution
 * strategies (subdomain, header, JWT claim, etc.).
 */
interface TenantServiceInterface
{
    /**
     * Create a new tenant with its default admin user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTenant(array $data): Tenant;

    /**
     * Find a tenant by its unique slug.
     */
    public function findBySlug(string $slug): ?Tenant;

    /**
     * Find a tenant by its UUID.
     */
    public function findById(string $id): ?Tenant;

    /**
     * List all tenants with optional pagination.
     *
     * @param  int  $perPage
     * @return LengthAwarePaginator<Tenant>
     */
    public function listTenants(int $perPage = 15): LengthAwarePaginator;

    /**
     * Update tenant metadata.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant;

    /**
     * Soft-delete (deactivate) a tenant and all its users.
     */
    public function deactivateTenant(Tenant $tenant): void;

    /**
     * Resolve the current tenant from an incoming request
     * (header, subdomain, or JWT claim).
     */
    public function resolveFromRequest(\Illuminate\Http\Request $request): ?Tenant;
}
