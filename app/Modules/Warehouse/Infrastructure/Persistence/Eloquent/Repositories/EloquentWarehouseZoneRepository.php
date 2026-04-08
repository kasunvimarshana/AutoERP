<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Exceptions\WarehouseZoneNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseZoneModel;

class EloquentWarehouseZoneRepository extends EloquentRepository implements WarehouseZoneRepositoryInterface
{
    public function __construct(WarehouseZoneModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (WarehouseZoneModel $model): WarehouseZone => $this->mapModelToDomainEntity($model));
    }

    /**
     * {@inheritdoc}
     */
    public function save(WarehouseZone $zone): WarehouseZone
    {
        $savedModel = null;

        DB::transaction(function () use ($zone, &$savedModel) {
            if ($zone->getId()) {
                $data = [
                    'warehouse_id'   => $zone->getWarehouseId(),
                    'tenant_id'      => $zone->getTenantId(),
                    'name'           => $zone->getName()->value(),
                    'type'           => $zone->getType(),
                    'code'           => $zone->getCode()?->value(),
                    'description'    => $zone->getDescription(),
                    'capacity'       => $zone->getCapacity(),
                    'metadata'       => $zone->getMetadata()?->toArray(),
                    'is_active'      => $zone->isActive(),
                    'parent_zone_id' => $zone->getParentZoneId(),
                ];
                $savedModel = $this->update($zone->getId(), $data);
            } else {
                $savedModel = $this->insertNode($zone);
            }
        });

        if (! $savedModel instanceof WarehouseZoneModel) {
            throw new \RuntimeException('Failed to save warehouse zone.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    /**
     * Insert a new zone node, calculating the correct lft/rgt values.
     */
    protected function insertNode(WarehouseZone $zone): WarehouseZoneModel
    {
        $parentZoneId = $zone->getParentZoneId();
        $warehouseId  = $zone->getWarehouseId();

        if ($parentZoneId === null) {
            $maxRgt = $this->model->where('warehouse_id', $warehouseId)
                ->whereNull('parent_zone_id')
                ->max('_rgt');
            $lft = ($maxRgt ?? 0) + 1;
            $rgt = $lft + 1;
        } else {
            $parent = $this->model->find($parentZoneId);
            if (! $parent) {
                throw new WarehouseZoneNotFoundException('parent');
            }
            $right = $parent->_rgt;
            $this->shiftLeftRight($warehouseId, $right, 2);
            $lft = $right;
            $rgt = $right + 1;
        }

        $zone->setLftRgt($lft, $rgt);

        return $this->model->create([
            'warehouse_id'   => $zone->getWarehouseId(),
            'tenant_id'      => $zone->getTenantId(),
            'name'           => $zone->getName()->value(),
            'type'           => $zone->getType(),
            'code'           => $zone->getCode()?->value(),
            'description'    => $zone->getDescription(),
            'capacity'       => $zone->getCapacity(),
            'metadata'       => $zone->getMetadata()?->toArray(),
            'is_active'      => $zone->isActive(),
            'parent_zone_id' => $zone->getParentZoneId(),
            '_lft'           => $lft,
            '_rgt'           => $rgt,
        ]);
    }

    /**
     * Shift left/right values for all zone nodes >= a given value within a warehouse.
     */
    protected function shiftLeftRight(int $warehouseId, int $from, int $delta): void
    {
        $this->model->where('warehouse_id', $warehouseId)
            ->where('_lft', '>=', $from)
            ->increment('_lft', $delta);
        $this->model->where('warehouse_id', $warehouseId)
            ->where('_rgt', '>=', $from)
            ->increment('_rgt', $delta);
    }

    /**
     * {@inheritdoc}
     */
    public function moveNode(int $id, ?int $newParentZoneId): void
    {
        $node = $this->model->find($id);
        if (! $node) {
            throw new WarehouseZoneNotFoundException;
        }

        if ($node->parent_zone_id === $newParentZoneId) {
            return;
        }

        DB::transaction(function () use ($node, $newParentZoneId) {
            $width = $node->_rgt - $node->_lft + 1;
            $this->shiftLeftRight($node->warehouse_id, $node->_rgt + 1, -$width);

            $node->parent_zone_id = $newParentZoneId;
            $node->save();

            if ($newParentZoneId === null) {
                $maxRgt = $this->model->where('warehouse_id', $node->warehouse_id)
                    ->whereNull('parent_zone_id')
                    ->max('_rgt');
                $newLft = ($maxRgt ?? 0) + 1;
            } else {
                $newParent = $this->model->find($newParentZoneId);
                if (! $newParent) {
                    throw new WarehouseZoneNotFoundException('parent');
                }
                $newLft = $newParent->_rgt;
                $this->shiftLeftRight($node->warehouse_id, $newLft, $width);
            }
            $newRgt = $newLft + $width - 1;
            $this->model->where('id', $node->id)->update(['_lft' => $newLft, '_rgt' => $newRgt]);

            $diff = (int) ($newLft - $node->_lft);
            if ($diff !== 0) {
                // Use query builder with DB::raw to atomically shift both _lft and _rgt
                // for all descendants. The cast to int ensures no injection risk.
                DB::table($this->model->getTable())
                    ->where('warehouse_id', $node->warehouse_id)
                    ->where('_lft', '>=', $node->_lft)
                    ->where('_rgt', '<=', $node->_rgt)
                    ->update([
                        '_lft' => DB::raw('_lft + '.(int) $diff),
                        '_rgt' => DB::raw('_rgt + '.(int) $diff),
                    ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getByWarehouse(int $warehouseId): array
    {
        return $this->model->where('warehouse_id', $warehouseId)
            ->orderBy('_lft')
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(WarehouseZoneModel $model): WarehouseZone
    {
        return new WarehouseZone(
            warehouseId:  $model->warehouse_id,
            tenantId:     $model->tenant_id,
            name:         new Name($model->name),
            type:         $model->type,
            code:         $model->code !== null ? new Code($model->code) : null,
            description:  $model->description,
            capacity:     isset($model->capacity) ? (float) $model->capacity : null,
            metadata:     isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            isActive:     (bool) $model->is_active,
            parentZoneId: $model->parent_zone_id,
            id:           $model->id,
            lft:          $model->_lft ?? 0,
            rgt:          $model->_rgt ?? 0,
            createdAt:    $model->created_at,
            updatedAt:    $model->updated_at
        );
    }
}
