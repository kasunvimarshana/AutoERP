<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Application\Repositories\LeaveApplicationRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveApplicationModel;

class EloquentLeaveApplicationRepository extends EloquentRepository implements LeaveApplicationRepositoryInterface
{
    public function __construct(LeaveApplicationModel $model)
    {
        parent::__construct($model);
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

    public function getByStatus(string $status, array $with = []): Collection
    {
        return $this->query($with)->where('status', $status)->get();
    }

    public function paginateByStatus(string $status, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('status', $status)->paginate($perPage);
    }
}

