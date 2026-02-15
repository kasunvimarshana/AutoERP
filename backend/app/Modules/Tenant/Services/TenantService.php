<?php

namespace App\Modules\Tenant\Services;

use App\Core\Services\BaseService;
use App\Modules\Tenant\Repositories\TenantRepositoryInterface;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService extends BaseService
{
    /**
     * TenantService constructor.
     *
     * @param TenantRepositoryInterface $repository
     */
    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new tenant with subscription
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure slug uniqueness
            $existing = $this->repository->findBySlug($data['slug']);
            if ($existing) {
                $data['slug'] = $data['slug'] . '-' . Str::random(4);
            }

            // Set trial period if applicable
            if (!isset($data['subscription_plan_id']) || !isset($data['subscription_starts_at'])) {
                $data['status'] = 'trial';
                $data['trial_ends_at'] = now()->addDays($data['trial_days'] ?? 14);
            }

            // Set default settings
            $data['settings'] = array_merge([
                'features' => [],
                'limits' => [],
            ], $data['settings'] ?? []);

            $tenant = $this->repository->create($data);

            DB::commit();

            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create tenant: ' . $e->getMessage());
        }
    }

    /**
     * Update tenant subscription
     *
     * @param int $tenantId
     * @param int $planId
     * @param array $subscriptionData
     * @return mixed
     */
    public function updateSubscription(int $tenantId, int $planId, array $subscriptionData = [])
    {
        DB::beginTransaction();

        try {
            $tenant = $this->repository->findById($tenantId);

            if (!$tenant) {
                throw new ServiceException('Tenant not found');
            }

            $updateData = [
                'subscription_plan_id' => $planId,
                'subscription_starts_at' => $subscriptionData['starts_at'] ?? now(),
                'subscription_ends_at' => $subscriptionData['ends_at'] ?? now()->addYear(),
                'status' => 'active',
            ];

            $this->repository->update($tenantId, $updateData);

            DB::commit();

            return $this->repository->findById($tenantId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to update subscription: ' . $e->getMessage());
        }
    }

    /**
     * Suspend tenant
     *
     * @param int $tenantId
     * @param string|null $reason
     * @return mixed
     */
    public function suspendTenant(int $tenantId, ?string $reason = null)
    {
        DB::beginTransaction();

        try {
            $tenant = $this->repository->findById($tenantId);

            if (!$tenant) {
                throw new ServiceException('Tenant not found');
            }

            $updateData = [
                'status' => 'suspended',
            ];

            if ($reason) {
                $metadata = $tenant->metadata ?? [];
                $metadata['suspension_reason'] = $reason;
                $metadata['suspended_at'] = now()->toDateTimeString();
                $updateData['metadata'] = $metadata;
            }

            $this->repository->update($tenantId, $updateData);

            DB::commit();

            return $this->repository->findById($tenantId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to suspend tenant: ' . $e->getMessage());
        }
    }

    /**
     * Activate tenant
     *
     * @param int $tenantId
     * @return mixed
     */
    public function activateTenant(int $tenantId)
    {
        DB::beginTransaction();

        try {
            $tenant = $this->repository->findById($tenantId);

            if (!$tenant) {
                throw new ServiceException('Tenant not found');
            }

            $updateData = ['status' => 'active'];

            $this->repository->update($tenantId, $updateData);

            DB::commit();

            return $this->repository->findById($tenantId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to activate tenant: ' . $e->getMessage());
        }
    }

    /**
     * Get tenants with expiring subscriptions
     *
     * @param int $daysThreshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringSubscriptions(int $daysThreshold = 7)
    {
        return $this->repository->getExpiringSubscriptions($daysThreshold);
    }

    /**
     * Get trial tenants
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrialTenants()
    {
        return $this->repository->getTrialTenants();
    }

    /**
     * Convert trial to paid subscription
     *
     * @param int $tenantId
     * @param int $planId
     * @param array $subscriptionData
     * @return mixed
     */
    public function convertTrialToSubscription(int $tenantId, int $planId, array $subscriptionData = [])
    {
        DB::beginTransaction();

        try {
            $tenant = $this->repository->findById($tenantId);

            if (!$tenant) {
                throw new ServiceException('Tenant not found');
            }

            if (!$tenant->isOnTrial()) {
                throw new ServiceException('Tenant is not on trial');
            }

            $updateData = [
                'subscription_plan_id' => $planId,
                'subscription_starts_at' => $subscriptionData['starts_at'] ?? now(),
                'subscription_ends_at' => $subscriptionData['ends_at'] ?? now()->addYear(),
                'status' => 'active',
                'trial_ends_at' => null,
            ];

            $this->repository->update($tenantId, $updateData);

            DB::commit();

            return $this->repository->findById($tenantId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to convert trial: ' . $e->getMessage());
        }
    }
}
