<?php

namespace App\Modules\Tenancy\Services;

use App\Core\Services\BaseService;
use App\Modules\Tenancy\Repositories\TenantRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Service
 *
 * Handles business logic for tenant management
 * Enforces transactional boundaries and data integrity
 */
class TenantService extends BaseService
{
    protected TenantRepository $repository;

    public function __construct(TenantRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new tenant
     *
     * @return mixed
     */
    public function createTenant(array $data)
    {
        DB::beginTransaction();

        try {
            // Validate subdomain uniqueness
            if ($this->repository->findBySubdomain($data['subdomain'])) {
                throw new \Exception('Subdomain already exists');
            }

            // Create tenant
            $tenant = $this->repository->create([
                'name' => $data['name'],
                'subdomain' => $data['subdomain'],
                'domain' => $data['domain'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'settings' => $data['settings'] ?? [],
                'trial_ends_at' => $data['trial_ends_at'] ?? now()->addDays(30),
            ]);

            DB::commit();

            Log::info('Tenant created successfully', ['tenant_id' => $tenant->id]);

            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating tenant: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update tenant settings
     */
    public function updateSettings(int $tenantId, array $settings): bool
    {
        DB::beginTransaction();

        try {
            $tenant = $this->repository->findOrFail($tenantId);

            $currentSettings = $tenant->settings ?? [];
            $newSettings = array_merge($currentSettings, $settings);

            $result = $this->repository->update($tenantId, [
                'settings' => $newSettings,
            ]);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating tenant settings: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Activate tenant
     */
    public function activate(int $tenantId): bool
    {
        return $this->update($tenantId, ['is_active' => true]);
    }

    /**
     * Deactivate tenant
     */
    public function deactivate(int $tenantId): bool
    {
        return $this->update($tenantId, ['is_active' => false]);
    }

    /**
     * Subscribe tenant
     */
    public function subscribe(int $tenantId): bool
    {
        return $this->update($tenantId, [
            'subscribed_at' => now(),
            'trial_ends_at' => null,
        ]);
    }
}
