<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryLocation;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLocationRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLocationModel;

class EloquentInventoryLocationRepository extends EloquentRepository implements InventoryLocationRepositoryInterface
{
    public function __construct(InventoryLocationModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventoryLocationModel $m): InventoryLocation => $this->mapModelToDomainEntity($m));
    }

    public function save(InventoryLocation $location): InventoryLocation
    {
        $savedModel = null;
        DB::transaction(function () use ($location, &$savedModel) {
            $data = [
                'tenant_id'    => $location->getTenantId(),
                'warehouse_id' => $location->getWarehouseId(),
                'zone_id'      => $location->getZoneId(),
                'code'         => $location->getCode(),
                'name'         => $location->getName(),
                'type'         => $location->getType(),
                'aisle'        => $location->getAisle(),
                'row'          => $location->getRow(),
                'level'        => $location->getLevel(),
                'bin'          => $location->getBin(),
                'capacity'     => $location->getCapacity(),
                'weight_limit' => $location->getWeightLimit(),
                'barcode'      => $location->getBarcode(),
                'qr_code'      => $location->getQrCode(),
                'is_pickable'  => $location->isPickable(),
                'is_storable'  => $location->isStorable(),
                'is_packing'   => $location->isPacking(),
                'is_active'    => $location->isActive(),
                'metadata'     => $location->getMetadata()->toArray(),
            ];
            if ($location->getId()) {
                $savedModel = $this->update($location->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventoryLocationModel) {
            throw new \RuntimeException('Failed to save InventoryLocation.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByCode(int $tenantId, string $code): ?InventoryLocation
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(InventoryLocationModel $model): InventoryLocation
    {
        return new InventoryLocation(
            tenantId:    $model->tenant_id,
            warehouseId: $model->warehouse_id,
            name:        $model->name,
            type:        $model->type,
            zoneId:      $model->zone_id,
            code:        $model->code,
            aisle:       $model->aisle,
            row:         $model->row,
            level:       $model->level,
            bin:         $model->bin,
            capacity:    isset($model->capacity) ? (float) $model->capacity : null,
            weightLimit: isset($model->weight_limit) ? (float) $model->weight_limit : null,
            barcode:     $model->barcode,
            qrCode:      $model->qr_code,
            isPickable:  (bool) $model->is_pickable,
            isStorable:  (bool) $model->is_storable,
            isPacking:   (bool) $model->is_packing,
            isActive:    (bool) $model->is_active,
            metadata:    isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:          $model->id,
            createdAt:   $model->created_at,
            updatedAt:   $model->updated_at,
        );
    }
}
