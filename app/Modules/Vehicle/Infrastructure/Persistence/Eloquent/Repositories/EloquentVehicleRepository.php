<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
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
        ?string $search,
        ?string $status,
        ?bool $serviceEnabled,
        ?bool $rentalEnabled,
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

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($query) use ($term): void {
                $query
                    ->where('vehicle_code', 'like', $term)
                    ->orWhere('license_plate', 'like', $term)
                    ->orWhere('vin', 'like', $term)
                    ->orWhere('make', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('category', 'like', $term);
            });
        }

        if ($status !== null && trim($status) !== '') {
            $query->where('status', trim($status));
        }

        if ($serviceEnabled !== null) {
            $query->where('service_enabled', $serviceEnabled);
        }

        if ($rentalEnabled !== null) {
            $query->where('rental_enabled', $rentalEnabled);
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

    public function findInTenant(int $tenantId, int|string $id): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function updateInTenant(int $tenantId, int|string $id, array $attributes): DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->firstOrFail();

        $model->fill($attributes);
        $model->save();

        return $this->toRecord($model);
    }

    public function deleteInTenant(int $tenantId, int|string $id): bool
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->firstOrFail();

        return (bool) $model->delete();
    }
}
