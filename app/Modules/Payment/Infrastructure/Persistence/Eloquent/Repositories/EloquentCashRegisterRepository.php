<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Application\Repositories\CashRegisterRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CashRegisterModel;

class EloquentCashRegisterRepository extends EloquentRepository implements CashRegisterRepositoryInterface
{
    public function __construct(CashRegisterModel $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, array $with = []): ?Model
    {
        return $this->query($with)->where('name', $name)->first();
    }

    public function getForTenant(int|string $tenantId, array $with = []): Collection
    {
        return $this->query($with)->where('tenant_id', $tenantId)->get();
    }

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model
    {
        return $this->query($with)->where('tenant_id', $tenantId)->whereKey($id)->first();
    }

    public function getForOrganizationUnit(int|string $organizationUnitId, array $with = []): Collection
    {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->get();
    }

    public function paginateForOrganizationUnit(int|string $organizationUnitId, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        return $this->query($with)->where('is_active', true)->get();
    }

    public function getInactive(array $with = []): Collection
    {
        return $this->query($with)->where('is_active', false)->get();
    }
}

