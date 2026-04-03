<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLevelModel;

class EloquentInventoryLevelRepository extends EloquentRepository implements InventoryLevelRepositoryInterface
{
    public function __construct(InventoryLevelModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventoryLevelModel $m): InventoryLevel => $this->mapModelToDomainEntity($m));
    }

    public function save(InventoryLevel $level): InventoryLevel
    {
        $savedModel = null;
        DB::transaction(function () use ($level, &$savedModel) {
            $data = [
                'tenant_id'    => $level->getTenantId(),
                'product_id'   => $level->getProductId(),
                'variation_id' => $level->getVariationId(),
                'location_id'  => $level->getLocationId(),
                'batch_id'     => $level->getBatchId(),
                'uom_id'       => $level->getUomId(),
                'qty_on_hand'  => $level->getQtyOnHand(),
                'qty_reserved' => $level->getQtyReserved(),
                'qty_available'=> $level->getQtyAvailable(),
                'qty_on_order' => $level->getQtyOnOrder(),
                'reorder_point'=> $level->getReorderPoint(),
                'reorder_qty'  => $level->getReorderQty(),
                'max_qty'      => $level->getMaxQty(),
                'min_qty'      => $level->getMinQty(),
                'last_counted_at' => $level->getLastCountedAt()?->format('Y-m-d H:i:s'),
            ];
            if ($level->getId()) {
                $savedModel = $this->update($level->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventoryLevelModel) {
            throw new \RuntimeException('Failed to save InventoryLevel.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByProductAndLocation(int $tenantId, int $productId, ?int $locationId, ?int $batchId): ?InventoryLevel
    {
        $query = $this->model->where('tenant_id', $tenantId)->where('product_id', $productId);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        } else {
            $query->whereNull('location_id');
        }

        if ($batchId !== null) {
            $query->where('batch_id', $batchId);
        } else {
            $query->whereNull('batch_id');
        }

        $model = $query->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function getTotalStock(int $tenantId, int $productId): float
    {
        return (float) $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)
            ->sum('qty_on_hand');
    }

    private function mapModelToDomainEntity(InventoryLevelModel $model): InventoryLevel
    {
        return new InventoryLevel(
            tenantId:      $model->tenant_id,
            productId:     $model->product_id,
            variationId:   $model->variation_id,
            locationId:    $model->location_id,
            batchId:       $model->batch_id,
            uomId:         $model->uom_id,
            qtyOnHand:     (float) $model->qty_on_hand,
            qtyReserved:   (float) $model->qty_reserved,
            qtyAvailable:  (float) $model->qty_available,
            qtyOnOrder:    (float) $model->qty_on_order,
            reorderPoint:  isset($model->reorder_point) ? (float) $model->reorder_point : null,
            reorderQty:    isset($model->reorder_qty) ? (float) $model->reorder_qty : null,
            maxQty:        isset($model->max_qty) ? (float) $model->max_qty : null,
            minQty:        isset($model->min_qty) ? (float) $model->min_qty : null,
            lastCountedAt: $model->last_counted_at,
            id:            $model->id,
            createdAt:     $model->created_at,
            updatedAt:     $model->updated_at,
        );
    }
}
