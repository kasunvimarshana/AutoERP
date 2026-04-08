<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountModel;

class EloquentInventoryCycleCountRepository extends EloquentRepository implements InventoryCycleCountRepositoryInterface
{
    public function __construct(InventoryCycleCountModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventoryCycleCountModel $m): InventoryCycleCount => $this->mapModelToDomainEntity($m));
    }

    public function save(InventoryCycleCount $count): InventoryCycleCount
    {
        $savedModel = null;
        DB::transaction(function () use ($count, &$savedModel) {
            $data = [
                'tenant_id'        => $count->getTenantId(),
                'reference_number' => $count->getReferenceNumber(),
                'warehouse_id'     => $count->getWarehouseId(),
                'zone_id'          => $count->getZoneId(),
                'location_id'      => $count->getLocationId(),
                'count_method'     => $count->getCountMethod(),
                'status'           => $count->getStatus(),
                'assigned_to'      => $count->getAssignedTo(),
                'scheduled_at'     => $count->getScheduledAt()?->format('Y-m-d H:i:s'),
                'started_at'       => $count->getStartedAt()?->format('Y-m-d H:i:s'),
                'completed_at'     => $count->getCompletedAt()?->format('Y-m-d H:i:s'),
                'notes'            => $count->getNotes(),
                'metadata'         => $count->getMetadata()->toArray(),
            ];
            if ($count->getId()) {
                $savedModel = $this->update($count->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventoryCycleCountModel) {
            throw new \RuntimeException('Failed to save InventoryCycleCount.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('warehouse_id', $warehouseId)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('status', $status)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(InventoryCycleCountModel $model): InventoryCycleCount
    {
        return new InventoryCycleCount(
            tenantId:        $model->tenant_id,
            referenceNumber: $model->reference_number,
            warehouseId:     $model->warehouse_id,
            zoneId:          $model->zone_id,
            locationId:      $model->location_id,
            countMethod:     $model->count_method,
            status:          $model->status,
            assignedTo:      $model->assigned_to,
            scheduledAt:     $model->scheduled_at,
            startedAt:       $model->started_at,
            completedAt:     $model->completed_at,
            notes:           $model->notes,
            metadata:        isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
