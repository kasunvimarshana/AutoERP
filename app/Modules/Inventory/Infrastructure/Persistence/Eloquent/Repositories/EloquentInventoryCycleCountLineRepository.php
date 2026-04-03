<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryCycleCountLine;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountLineRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountLineModel;

class EloquentInventoryCycleCountLineRepository extends EloquentRepository implements InventoryCycleCountLineRepositoryInterface
{
    public function __construct(InventoryCycleCountLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventoryCycleCountLineModel $m): InventoryCycleCountLine => $this->mapModelToDomainEntity($m));
    }

    public function save(InventoryCycleCountLine $line): InventoryCycleCountLine
    {
        $savedModel = null;
        DB::transaction(function () use ($line, &$savedModel) {
            $data = [
                'tenant_id'       => $line->getTenantId(),
                'cycle_count_id'  => $line->getCycleCountId(),
                'product_id'      => $line->getProductId(),
                'variation_id'    => $line->getVariationId(),
                'batch_id'        => $line->getBatchId(),
                'serial_number_id'=> $line->getSerialNumberId(),
                'location_id'     => $line->getLocationId(),
                'expected_qty'    => $line->getExpectedQty(),
                'counted_qty'     => $line->getCountedQty(),
                'variance_qty'    => $line->getVarianceQty(),
                'status'          => $line->getStatus(),
                'counted_at'      => $line->getCountedAt()?->format('Y-m-d H:i:s'),
                'counted_by'      => $line->getCountedBy(),
                'notes'           => $line->getNotes(),
            ];
            if ($line->getId()) {
                $savedModel = $this->update($line->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventoryCycleCountLineModel) {
            throw new \RuntimeException('Failed to save InventoryCycleCountLine.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByCycleCount(int $tenantId, int $cycleCountId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('cycle_count_id', $cycleCountId)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(InventoryCycleCountLineModel $model): InventoryCycleCountLine
    {
        return new InventoryCycleCountLine(
            tenantId:       $model->tenant_id,
            cycleCountId:   $model->cycle_count_id,
            productId:      $model->product_id,
            expectedQty:    (float) $model->expected_qty,
            variationId:    $model->variation_id,
            batchId:        $model->batch_id,
            serialNumberId: $model->serial_number_id,
            locationId:     $model->location_id,
            countedQty:     isset($model->counted_qty) ? (float) $model->counted_qty : null,
            varianceQty:    (float) $model->variance_qty,
            status:         $model->status,
            countedAt:      $model->counted_at,
            countedBy:      $model->counted_by,
            notes:          $model->notes,
            id:             $model->id,
            createdAt:      $model->created_at,
            updatedAt:      $model->updated_at,
        );
    }
}
