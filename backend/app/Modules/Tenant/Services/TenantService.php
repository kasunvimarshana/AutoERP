<?php

namespace App\Modules\Tenant\Services;

use App\Base\BaseService;
use App\Modules\Tenant\Repositories\TenantRepository;
use App\Modules\Tenant\DTOs\TenantDTO;
use App\Modules\Tenant\Events\TenantCreated;
use App\Modules\Tenant\Events\TenantUpdated;
use App\Modules\Tenant\Events\TenantDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Exception;

class TenantService extends BaseService
{
    /**
     * TenantService constructor.
     *
     * @param TenantRepository $repository
     */
    public function __construct(TenantRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new tenant
     */
    public function createTenant(TenantDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $tenant = $this->repository->create($dto->toArray());
            
            // Create default domain
            if ($dto->domain) {
                $tenant->domains()->create([
                    'domain' => $dto->domain,
                ]);
            }

            Event::dispatch(new TenantCreated($tenant));
            
            return $tenant;
        });
    }

    /**
     * Update tenant
     */
    public function updateTenant(string $id, TenantDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $result = $this->repository->update($id, $dto->toArray());
            
            if ($result) {
                $tenant = $this->repository->find($id);
                Event::dispatch(new TenantUpdated($tenant));
            }
            
            return $result;
        });
    }

    /**
     * Delete tenant
     */
    public function deleteTenant(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $tenant = $this->repository->find($id);
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new TenantDeleted($tenant));
            }
            
            return $result;
        });
    }

    /**
     * Suspend tenant
     */
    public function suspendTenant(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            return $this->repository->suspend($id);
        });
    }

    /**
     * Activate tenant
     */
    public function activateTenant(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            return $this->repository->activate($id);
        });
    }

    /**
     * Find tenant by domain
     */
    public function findByDomain(string $domain): ?Model
    {
        return $this->repository->findByDomain($domain);
    }

    /**
     * Get active tenants
     */
    public function getActiveTenants()
    {
        return $this->repository->getActive();
    }
}
