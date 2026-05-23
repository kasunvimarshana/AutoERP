<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use App\Support\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;

class EloquentVehicleDocumentRepository extends EloquentRepository implements VehicleDocumentRepositoryInterface
{
    public function __construct(VehicleDocumentModel $model)
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

    public function paginateForOrganizationUnit(
        int|string $organizationUnitId,
        int $perPage = 15,
        array $with = [],
    ): LengthAwarePaginator {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->paginate($perPage);
    }

    public function getForVehicle(int|string $vehicleId, array $with = []): Collection
    {
        return $this->query($with)->where('vehicle_id', $vehicleId)->get();
    }

    public function paginateForVehicle(
        int|string $vehicleId,
        int $perPage = 15,
        array $with = [],
    ): LengthAwarePaginator {
        return $this->query($with)->where('vehicle_id', $vehicleId)->paginate($perPage);
    }

    public function findForTenantAndVehicleById(
        int|string $tenantId,
        int|string $vehicleId,
        int|string $id,
        array $with = [],
    ): ?Model {
        return $this->query($with)
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereKey($id)
            ->first();
    }
}
