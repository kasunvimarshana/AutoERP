<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnLineModel;

class EloquentStockReturnLineRepository extends EloquentRepository implements StockReturnLineRepositoryInterface
{
    public function __construct(StockReturnLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (StockReturnLineModel $m): StockReturnLine => $this->mapModelToDomainEntity($m));
    }

    public function save(StockReturnLine $line): StockReturnLine
    {
        $savedModel = null;

        DB::transaction(function () use ($line, &$savedModel) {
            $data = [
                'tenant_id'          => $line->getTenantId(),
                'stock_return_id'    => $line->getStockReturnId(),
                'product_id'         => $line->getProductId(),
                'variation_id'       => $line->getVariationId(),
                'batch_id'           => $line->getBatchId(),
                'serial_number_id'   => $line->getSerialNumberId(),
                'uom_id'             => $line->getUomId(),
                'quantity_requested' => $line->getQuantityRequested(),
                'quantity_approved'  => $line->getQuantityApproved(),
                'unit_price'         => $line->getUnitPrice(),
                'unit_cost'          => $line->getUnitCost(),
                'condition'          => $line->getCondition(),
                'disposition'        => $line->getDisposition(),
                'quality_check_status' => $line->getQualityCheckStatus(),
                'quality_checked_by' => $line->getQualityCheckedBy(),
                'quality_checked_at' => $line->getQualityCheckedAt()?->format('Y-m-d H:i:s'),
                'notes'              => $line->getNotes(),
            ];

            if ($line->getId()) {
                $savedModel = $this->update($line->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof StockReturnLineModel) {
            throw new \RuntimeException('Failed to save StockReturnLine.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByReturn(int $tenantId, int $returnId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('stock_return_id', $returnId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(StockReturnLineModel $model): StockReturnLine
    {
        return new StockReturnLine(
            tenantId:           $model->tenant_id,
            stockReturnId:      $model->stock_return_id,
            productId:          $model->product_id,
            quantityRequested:  (float) $model->quantity_requested,
            variationId:        $model->variation_id,
            batchId:            $model->batch_id,
            serialNumberId:     $model->serial_number_id,
            uomId:              $model->uom_id,
            quantityApproved:   isset($model->quantity_approved) ? (float) $model->quantity_approved : null,
            unitPrice:          isset($model->unit_price) ? (float) $model->unit_price : null,
            unitCost:           isset($model->unit_cost) ? (float) $model->unit_cost : null,
            condition:          $model->condition,
            disposition:        $model->disposition,
            qualityCheckStatus: $model->quality_check_status,
            qualityCheckedBy:   $model->quality_checked_by,
            qualityCheckedAt:   $model->quality_checked_at,
            notes:              $model->notes,
            id:                 $model->id,
            createdAt:          $model->created_at,
            updatedAt:          $model->updated_at,
        );
    }
}
