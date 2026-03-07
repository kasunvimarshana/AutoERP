<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TenantServiceInterface;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Concrete implementation of TenantServiceInterface.
 *
 * Manages tenant lifecycle and resolves the current tenant
 * from incoming HTTP requests via a custom X-Tenant-ID header
 * (can be extended to support subdomains or JWT claims).
 */
final class TenantService implements TenantServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function createTenant(array $data): Tenant
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        $tenant = Tenant::create([
            'name'      => $data['name'],
            'slug'      => $slug,
            'plan'      => $data['plan'] ?? 'free',
            'is_active' => true,
            'settings'  => $data['settings'] ?? [],
        ]);

        Log::info('Tenant created', ['tenant_id' => $tenant->id, 'slug' => $tenant->slug]);

        return $tenant;
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?Tenant
    {
        return Tenant::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function listTenants(int $perPage = 15): LengthAwarePaginator
    {
        return Tenant::orderBy('name')->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        Log::info('Tenant updated', ['tenant_id' => $tenant->id]);

        return $tenant->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateTenant(Tenant $tenant): void
    {
        // Deactivate all users in the tenant
        $tenant->users()->update(['is_active' => false]);

        $tenant->delete(); // SoftDeletes

        Log::warning('Tenant deactivated', ['tenant_id' => $tenant->id]);
    }

    /**
     * {@inheritDoc}
     *
     * Resolution order:
     *   1. X-Tenant-ID request header (UUID)
     *   2. X-Tenant-Slug request header (slug)
     *   3. tenant_id claim from Passport JWT token
     */
    public function resolveFromRequest(Request $request): ?Tenant
    {
        if ($tenantId = $request->header('X-Tenant-ID')) {
            return $this->findById((string) $tenantId);
        }

        if ($tenantSlug = $request->header('X-Tenant-Slug')) {
            return $this->findBySlug((string) $tenantSlug);
        }

        // Fall back to authenticated user's tenant
        if ($user = $request->user()) {
            return $user->tenant;
        }

        return null;
    }
}
