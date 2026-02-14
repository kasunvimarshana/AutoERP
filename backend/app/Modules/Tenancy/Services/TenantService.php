<?php

namespace App\Modules\Tenancy\Services;

use App\Core\BaseService;
use App\Modules\Tenancy\Repositories\TenantRepository;
use App\Modules\Tenancy\Events\TenantCreated;
use App\Modules\Tenancy\Events\TenantUpdated;
use App\Modules\Tenancy\Events\TenantDeleted;
use Illuminate\Support\Facades\Event;

/**
 * Tenant Service
 * 
 * @package App\Modules\Tenancy\Services
 */
class TenantService extends BaseService
{
    /**
     * @var TenantRepository
     */
    protected TenantRepository $repository;

    /**
     * TenantService constructor.
     *
     * @param TenantRepository $repository
     */
    public function __construct(TenantRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new tenant
     *
     * @param array $data
     * @return \App\Modules\Tenancy\Models\Tenant
     */
    public function createTenant(array $data)
    {
        return $this->transaction(function () use ($data) {
            $tenant = $this->repository->create($data);
            
            Event::dispatch(new TenantCreated($tenant));
            
            $this->logActivity('Tenant created', ['tenant_id' => $tenant->id]);
            
            return $tenant;
        });
    }

    /**
     * Update tenant
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateTenant(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $tenant = $this->repository->find($id);
                Event::dispatch(new TenantUpdated($tenant));
                $this->logActivity('Tenant updated', ['tenant_id' => $id]);
            }
            
            return $result;
        });
    }

    /**
     * Delete tenant
     *
     * @param int $id
     * @return bool
     */
    public function deleteTenant(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $tenant = $this->repository->find($id);
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new TenantDeleted($tenant));
                $this->logActivity('Tenant deleted', ['tenant_id' => $id]);
            }
            
            return $result;
        });
    }

    /**
     * Get tenant by ID
     *
     * @param int $id
     * @return \App\Modules\Tenancy\Models\Tenant|null
     */
    public function getTenant(int $id)
    {
        return $this->repository->find($id);
    }

    /**
     * Get all tenants
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllTenants()
    {
        return $this->repository->all();
    }
}
