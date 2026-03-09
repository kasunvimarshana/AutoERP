<?php

namespace App\Domain\Contracts;

use App\Domain\Models\Tenant;
use App\Domain\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TenantServiceInterface
{
    /**
     * List tenants with optional filters.
     */
    public function list(array $params = []): LengthAwarePaginator|Collection;

    /**
     * Create a new tenant and provision its database schema.
     */
    public function create(array $data): Tenant;

    /**
     * Update tenant data.
     */
    public function update(string $tenantId, array $data): Tenant;

    /**
     * Delete a tenant and its associated resources.
     */
    public function delete(string $tenantId): bool;

    /**
     * Find a tenant or throw an exception.
     */
    public function findOrFail(string $tenantId): Tenant;

    /**
     * Find a tenant by subdomain.
     */
    public function findBySubdomain(string $subdomain): ?Tenant;

    /**
     * Switch tenant context for an authenticated user.
     *
     * @return array{access_token: string, refresh_token: string|null, expires_in: int, tenant: Tenant}
     */
    public function switchTenant(User $user, string $targetTenantId, ?string $deviceId = null): array;

    /**
     * Resolve tenant from the current request.
     */
    public function resolveFromRequest(\Illuminate\Http\Request $request): ?Tenant;

    /**
     * Provision database schema / connection for a tenant.
     */
    public function provisionDatabase(Tenant $tenant): void;
}
