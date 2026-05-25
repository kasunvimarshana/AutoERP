<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;

final class EloquentVehicleDocumentRepository extends EloquentRepository implements VehicleDocumentRepositoryInterface
{
    public function __construct(VehicleDocumentModel $model)
    {
        parent::__construct($model);
    }

    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?int $vehicleId,
        ?string $name,
        ?string $type,
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

        if ($vehicleId !== null) {
            $query->where('vehicle_id', $vehicleId);
        }

        if ($name !== null && trim($name) !== '') {
            $query->where('name', trim($name));
        }

        if ($type !== null && trim($type) !== '') {
            $query->where('type', trim($type));
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
