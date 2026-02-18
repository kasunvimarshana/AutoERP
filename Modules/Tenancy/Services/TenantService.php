<?php

declare(strict_types=1);

namespace Modules\Tenancy\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Tenancy\Entities\Tenant;
use Modules\Tenancy\Events\TenantCreated;
use Modules\Tenancy\Events\TenantDeleted;
use Modules\Tenancy\Events\TenantUpdated;
use Modules\Tenancy\Repositories\Contracts\TenantRepositoryInterface;

/**
 * Tenant Service
 *
 * Handles business logic for tenant management operations.
 * Manages tenant lifecycle including creation, updates, activation, and suspension.
 */
class TenantService extends BaseService
{
    /**
     * TenantService constructor.
     */
    public function __construct(
        protected TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * Get paginated tenants.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->tenantRepository->paginate($perPage);
    }

    /**
     * Create a new tenant.
     */
    public function create(array $data): Tenant
    {
        return $this->executeInTransaction(function () use ($data) {
            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }

            $tenant = $this->tenantRepository->create($data);

            event(new TenantCreated($tenant));

            $this->logInfo('Tenant created', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
            ]);

            return $tenant;
        });
    }

    /**
     * Update an existing tenant.
     */
    public function update(int|string $id, array $data): Tenant
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $tenant = $this->tenantRepository->update($id, $data);

            event(new TenantUpdated($tenant));

            $this->logInfo('Tenant updated', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);

            return $tenant;
        });
    }

    /**
     * Delete a tenant.
     */
    public function delete(int|string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $tenant = $this->tenantRepository->findById($id);

            if (! $tenant) {
                $this->logWarning('Tenant not found for deletion', ['tenant_id' => $id]);

                return false;
            }

            $result = $this->tenantRepository->delete($id);

            if ($result) {
                event(new TenantDeleted($tenant));

                $this->logInfo('Tenant deleted', [
                    'tenant_id' => $id,
                    'tenant_name' => $tenant->name,
                ]);
            }

            return $result;
        });
    }

    /**
     * Get tenant by ID.
     */
    public function findById(int|string $id): ?Tenant
    {
        return $this->tenantRepository->findById($id);
    }

    /**
     * Find tenant by slug.
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return $this->tenantRepository->findBySlug($slug);
    }

    /**
     * Find tenant by domain.
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->tenantRepository->findByDomain($domain);
    }

    /**
     * Get all active tenants.
     */
    public function getActiveTenants(): Collection
    {
        return $this->tenantRepository->getActiveTenants();
    }

    /**
     * Search tenants.
     */
    public function search(string $query): Collection
    {
        return $this->tenantRepository->search($query);
    }

    /**
     * Activate a tenant.
     */
    public function activate(int|string $id): Tenant
    {
        return $this->executeInTransaction(function () use ($id) {
            $tenant = $this->tenantRepository->update($id, ['status' => 'active']);

            $this->logInfo('Tenant activated', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);

            return $tenant;
        });
    }

    /**
     * Deactivate a tenant.
     */
    public function deactivate(int|string $id): Tenant
    {
        return $this->executeInTransaction(function () use ($id) {
            $tenant = $this->tenantRepository->update($id, ['status' => 'inactive']);

            $this->logInfo('Tenant deactivated', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);

            return $tenant;
        });
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(int|string $id): Tenant
    {
        return $this->executeInTransaction(function () use ($id) {
            $tenant = $this->tenantRepository->update($id, ['status' => 'suspended']);

            $this->logInfo('Tenant suspended', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);

            return $tenant;
        });
    }
}
