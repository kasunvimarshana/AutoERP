<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

final class EloquentVehicleRepository extends EloquentRepository implements VehicleRepositoryInterface
{
    public function __construct(VehicleModel $model)
    {
        parent::__construct($model);
    }

    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?string $vehicleCode,
        ?string $vin,
        ?string $licensePlate,
        ?string $status,
        int $perPage,
        int $page,
    ): PagedResult {
        $query = $this->query();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($organizationUnitId !== null) {
            $query->where('organization_unit_id', $organizationUnitId);
        }

        if ($vehicleCode !== null && trim($vehicleCode) !== '') {
            $query->where('vehicle_code', trim($vehicleCode));
        }

        if ($vin !== null && trim($vin) !== '') {
            $query->where('vin', trim($vin));
        }

        if ($licensePlate !== null && trim($licensePlate) !== '') {
            $query->where('license_plate', trim($licensePlate));
        }

        if ($status !== null && trim($status) !== '') {
            $query->where('status', trim($status));
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }
}
