<?php

namespace App\Modules\TenantManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\TenantManagement\Repositories\TenantRepository;
use App\Modules\TenantManagement\Events\TenantCreated;
use App\Modules\TenantManagement\Events\TenantUpdated;
use App\Modules\TenantManagement\Events\TenantSubscriptionUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantService extends BaseService
{
    public function __construct(TenantRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function afterCreate($tenant, array $data): void
    {
        event(new TenantCreated($tenant));
        Log::info('Tenant created', ['tenant_id' => $tenant->id, 'tenant_name' => $tenant->name]);
    }

    protected function afterUpdate($tenant, array $data): void
    {
        event(new TenantUpdated($tenant));
        Log::info('Tenant updated', ['tenant_id' => $tenant->id]);
    }

    public function findBySlug(string $slug)
    {
        return $this->repository->findBySlug($slug);
    }

    public function findByDomain(string $domain)
    {
        return $this->repository->findByDomain($domain);
    }

    public function findByUuid(string $uuid)
    {
        return $this->repository->findByUuid($uuid);
    }

    public function getActive()
    {
        return $this->repository->getActive();
    }

    public function getWithActiveSubscription()
    {
        return $this->repository->getWithActiveSubscription();
    }

    public function search(array $criteria)
    {
        return $this->repository->search($criteria);
    }

    public function activate(int $id)
    {
        try {
            DB::beginTransaction();

            $tenant = $this->repository->update($id, ['status' => 'active']);
            
            DB::commit();

            Log::info('Tenant activated', ['tenant_id' => $id]);
            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to activate tenant', ['tenant_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function suspend(int $id)
    {
        try {
            DB::beginTransaction();

            $tenant = $this->repository->update($id, ['status' => 'suspended']);
            
            DB::commit();

            Log::info('Tenant suspended', ['tenant_id' => $id]);
            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to suspend tenant', ['tenant_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateSubscription(int $id, array $subscriptionData)
    {
        try {
            DB::beginTransaction();

            $tenant = $this->repository->updateSubscription($id, $subscriptionData);
            
            DB::commit();

            event(new TenantSubscriptionUpdated($tenant));
            Log::info('Tenant subscription updated', ['tenant_id' => $id, 'plan' => $subscriptionData['subscription_plan'] ?? null]);
            
            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update tenant subscription', ['tenant_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function renewSubscription(int $id, int $months = 1)
    {
        try {
            DB::beginTransaction();

            $tenant = $this->repository->findById($id);
            $expiresAt = $tenant->subscription_expires_at ?? now();
            
            if ($expiresAt->isPast()) {
                $expiresAt = now();
            }

            $newExpiresAt = $expiresAt->addMonths($months);

            $tenant = $this->repository->update($id, [
                'subscription_status' => 'active',
                'subscription_expires_at' => $newExpiresAt,
            ]);
            
            DB::commit();

            Log::info('Tenant subscription renewed', ['tenant_id' => $id, 'expires_at' => $newExpiresAt]);
            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to renew tenant subscription', ['tenant_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
