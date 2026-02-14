<?php

namespace App\Services;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tenant Service
 * 
 * Handles business logic for tenant management including
 * creation, updates, status changes, and lifecycle management.
 */
class TenantService extends BaseService
{
    /**
     * Constructor
     *
     * @param TenantRepositoryInterface $repository
     */
    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Find tenant by slug
     *
     * @param string $slug
     * @return Tenant|null
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return $this->repository->findBySlug($slug);
    }

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return Tenant|null
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->repository->findByDomain($domain);
    }

    /**
     * Find tenant by subdomain
     *
     * @param string $subdomain
     * @return Tenant|null
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return $this->repository->findBySubdomain($subdomain);
    }

    /**
     * Get active tenants
     *
     * @return Collection
     */
    public function getActiveTenants(): Collection
    {
        return $this->repository->getActiveTenants();
    }

    /**
     * Get tenants on trial
     *
     * @return Collection
     */
    public function getTenantsOnTrial(): Collection
    {
        return $this->repository->getTenantsOnTrial();
    }

    /**
     * Get expired trials
     *
     * @return Collection
     */
    public function getExpiredTrials(): Collection
    {
        return $this->repository->getExpiredTrials();
    }

    /**
     * Activate a tenant
     *
     * @param int $id
     * @return Tenant
     */
    public function activate(int $id): Tenant
    {
        return DB::transaction(function () use ($id) {
            $tenant = $this->getById($id);
            $tenant->activate();
            
            // Fire TenantActivated event
            event(new \App\Events\TenantActivated($tenant));
            
            return $tenant->fresh();
        });
    }

    /**
     * Suspend a tenant
     *
     * @param int $id
     * @return Tenant
     */
    public function suspend(int $id): Tenant
    {
        return DB::transaction(function () use ($id) {
            $tenant = $this->getById($id);
            $tenant->suspend();
            
            // Fire TenantSuspended event
            event(new \App\Events\TenantSuspended($tenant));
            
            return $tenant->fresh();
        });
    }

    /**
     * Cancel a tenant
     *
     * @param int $id
     * @return Tenant
     */
    public function cancel(int $id): Tenant
    {
        return DB::transaction(function () use ($id) {
            $tenant = $this->getById($id);
            $tenant->cancel();
            
            // Fire TenantCancelled event
            event(new \App\Events\TenantCancelled($tenant));
            
            return $tenant->fresh();
        });
    }

    /**
     * Extend trial period
     *
     * @param int $id
     * @param int $days
     * @return Tenant
     */
    public function extendTrial(int $id, int $days): Tenant
    {
        return DB::transaction(function () use ($id, $days) {
            $tenant = $this->getById($id);
            
            $currentTrialEnd = $tenant->trial_ends_at ?: now();
            $tenant->trial_ends_at = $currentTrialEnd->addDays($days);
            $tenant->save();
            
            return $tenant->fresh();
        });
    }

    /**
     * Hook: Before create
     * Generate slug if not provided
     *
     * @param array $data
     * @return array
     */
    protected function beforeCreate(array $data): array
    {
        // Generate slug from name if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
            
            // Ensure uniqueness
            $count = 1;
            $originalSlug = $data['slug'];
            while ($this->repository->findBySlug($data['slug'])) {
                $data['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }
        
        // Set default trial period if not provided (14 days)
        if (empty($data['trial_ends_at']) && $data['status'] === 'trial') {
            $data['trial_ends_at'] = now()->addDays(14);
        }
        
        return $data;
    }

    /**
     * Hook: After create
     * Fire TenantCreated event
     *
     * @param Tenant $model
     * @param array $data
     * @return void
     */
    protected function afterCreate($model, array $data): void
    {
        // Fire TenantCreated event
        event(new \App\Events\TenantCreated($model));
    }

    /**
     * Hook: After update
     * Fire TenantUpdated event
     *
     * @param Tenant $model
     * @param array $data
     * @return void
     */
    protected function afterUpdate($model, array $data): void
    {
        // Fire TenantUpdated event
        event(new \App\Events\TenantUpdated($model));
    }

    /**
     * Hook: Before delete
     * Ensure tenant can be deleted (no active users, etc.)
     *
     * @param Tenant $model
     * @return void
     * @throws \Exception
     */
    protected function beforeDelete($model): void
    {
        // Check if tenant has users
        if ($model->users()->count() > 0) {
            throw new \Exception('Cannot delete tenant with existing users. Please remove all users first.');
        }
    }

    /**
     * Hook: After delete
     * Fire TenantDeleted event
     *
     * @param Tenant $model
     * @return void
     */
    protected function afterDelete($model): void
    {
        // Fire TenantDeleted event
        event(new \App\Events\TenantDeleted($model));
    }
}
