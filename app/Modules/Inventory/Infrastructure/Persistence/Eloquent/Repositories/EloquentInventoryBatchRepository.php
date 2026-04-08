<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryBatchModel;

class EloquentInventoryBatchRepository extends EloquentRepository implements InventoryBatchRepositoryInterface
{
    public function __construct(InventoryBatchModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventoryBatchModel $m): InventoryBatch => $this->mapModelToDomainEntity($m));
    }

    public function save(InventoryBatch $batch): InventoryBatch
    {
        $savedModel = null;
        DB::transaction(function () use ($batch, &$savedModel) {
            $data = [
                'tenant_id'         => $batch->getTenantId(),
                'product_id'        => $batch->getProductId(),
                'variation_id'      => $batch->getVariationId(),
                'batch_number'      => $batch->getBatchNumber(),
                'lot_number'        => $batch->getLotNumber(),
                'manufacture_date'  => $batch->getManufactureDate()?->format('Y-m-d'),
                'expiry_date'       => $batch->getExpiryDate()?->format('Y-m-d'),
                'best_before_date'  => $batch->getBestBeforeDate()?->format('Y-m-d'),
                'supplier_id'       => $batch->getSupplierId(),
                'supplier_batch_ref'=> $batch->getSupplierBatchRef(),
                'initial_qty'       => $batch->getInitialQty(),
                'remaining_qty'     => $batch->getRemainingQty(),
                'unit_cost'         => $batch->getUnitCost(),
                'currency'          => $batch->getCurrency(),
                'status'            => $batch->getStatus(),
                'notes'             => $batch->getNotes(),
                'metadata'          => $batch->getMetadata()->toArray(),
            ];
            if ($batch->getId()) {
                $savedModel = $this->update($batch->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventoryBatchModel) {
            throw new \RuntimeException('Failed to save InventoryBatch.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findActiveBatches(int $tenantId, int $productId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)
            ->where('status', 'active')->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByBatchNumber(int $tenantId, string $batchNumber, int $productId): ?InventoryBatch
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('batch_number', $batchNumber)
            ->where('product_id', $productId)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(InventoryBatchModel $model): InventoryBatch
    {
        return new InventoryBatch(
            tenantId:         $model->tenant_id,
            productId:        $model->product_id,
            batchNumber:      $model->batch_number,
            variationId:      $model->variation_id,
            lotNumber:        $model->lot_number,
            manufactureDate:  $model->manufacture_date,
            expiryDate:       $model->expiry_date,
            bestBeforeDate:   $model->best_before_date,
            supplierId:       $model->supplier_id,
            supplierBatchRef: $model->supplier_batch_ref,
            initialQty:       (float) $model->initial_qty,
            remainingQty:     (float) $model->remaining_qty,
            unitCost:         (float) $model->unit_cost,
            currency:         $model->currency,
            status:           $model->status,
            notes:            $model->notes,
            metadata:         isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:               $model->id,
            createdAt:        $model->created_at,
            updatedAt:        $model->updated_at,
        );
    }
}
