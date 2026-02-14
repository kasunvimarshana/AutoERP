<?php

namespace App\Modules\Tenant\Services;

use App\Core\Services\BaseService;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Repositories\TenantRepository;
use Illuminate\Support\Str;

/**
 * Tenant Service
 *
 * Handles business logic for tenant operations.
 */
class TenantService extends BaseService
{
    public function __construct(TenantRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new tenant with subscription.
     */
    public function createTenant(array $data): Tenant
    {
        return $this->transaction(function () use ($data) {
            // Generate slug if not provided
            if (! isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Set trial period if not subscribed immediately
            if (! isset($data['subscribed_at'])) {
                $data['trial_ends_at'] = now()->addDays(14); // 14-day trial
            }

            // Set default status
            $data['status'] = $data['status'] ?? 'active';

            return $this->repository->create($data);
        });
    }

    /**
     * Find tenant by slug or domain.
     */
    public function findTenant(string $identifier): ?Tenant
    {
        // Try to find by domain first
        $tenant = $this->repository->findByDomain($identifier);

        // If not found, try by slug
        if (! $tenant) {
            $tenant = $this->repository->findBySlug($identifier);
        }

        return $tenant;
    }

    /**
     * Suspend a tenant.
     */
    public function suspendTenant(int $id): bool
    {
        return $this->update($id, ['status' => 'suspended']);
    }

    /**
     * Activate a tenant.
     */
    public function activateTenant(int $id): bool
    {
        return $this->update($id, ['status' => 'active']);
    }
}
