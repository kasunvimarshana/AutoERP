<?php

declare(strict_types=1);

namespace Modules\SystemUser\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models\SystemUserModel;

class EloquentSystemUserRepository extends EloquentRepository implements SystemUserRepositoryInterface
{
    public function __construct(SystemUserModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code, array $with = []): ?Model
    {
        return $this->query($with)->where('code', $code)->first();
    }

    public function findByRegistrationNumber(string $registrationNumber, array $with = []): ?Model
    {
        return $this->query($with)->where('registration_number', $registrationNumber)->first();
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

    public function paginateForOrganizationUnit(
        int|string $organizationUnitId,
        int $perPage = 15,
        array $with = [],
    ): LengthAwarePaginator
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

