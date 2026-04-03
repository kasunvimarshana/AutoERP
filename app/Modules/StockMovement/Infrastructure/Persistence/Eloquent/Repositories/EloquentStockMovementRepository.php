<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;

class EloquentStockMovementRepository extends EloquentRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovementModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (StockMovementModel $m): StockMovement => $this->mapModelToDomainEntity($m));
    }

    public function save(StockMovement $movement): StockMovement
    {
        $savedModel = null;
        DB::transaction(function () use ($movement, &$savedModel) {
            $data = [
                'tenant_id'        => $movement->getTenantId(),
                'reference_number' => $movement->getReferenceNumber(),
                'movement_type'    => $movement->getMovementType(),
                'status'           => $movement->getStatus(),
                'product_id'       => $movement->getProductId(),
                'variation_id'     => $movement->getVariationId(),
                'from_location_id' => $movement->getFromLocationId(),
                'to_location_id'   => $movement->getToLocationId(),
                'batch_id'         => $movement->getBatchId(),
                'serial_number_id' => $movement->getSerialNumberId(),
                'uom_id'           => $movement->getUomId(),
                'quantity'         => $movement->getQuantity(),
                'unit_cost'        => $movement->getUnitCost(),
                'currency'         => $movement->getCurrency(),
                'reference_type'   => $movement->getReferenceType(),
                'reference_id'     => $movement->getReferenceId(),
                'performed_by'     => $movement->getPerformedBy(),
                'movement_date'    => $movement->getMovementDate()?->format('Y-m-d H:i:s'),
                'notes'            => $movement->getNotes(),
                'metadata'         => $movement->getMetadata()->toArray(),
            ];
            if ($movement->getId()) {
                $savedModel = $this->update($movement->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof StockMovementModel) {
            throw new \RuntimeException('Failed to save StockMovement.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByReferenceNumber(int $tenantId, string $ref): ?StockMovement
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('reference_number', $ref)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByMovementType(int $tenantId, string $type): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('movement_type', $type)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(StockMovementModel $model): StockMovement
    {
        return new StockMovement(
            tenantId:        $model->tenant_id,
            referenceNumber: $model->reference_number,
            movementType:    $model->movement_type,
            productId:       $model->product_id,
            quantity:        (float) $model->quantity,
            variationId:     $model->variation_id,
            fromLocationId:  $model->from_location_id,
            toLocationId:    $model->to_location_id,
            batchId:         $model->batch_id,
            serialNumberId:  $model->serial_number_id,
            uomId:           $model->uom_id,
            unitCost:        isset($model->unit_cost) ? (float) $model->unit_cost : null,
            currency:        $model->currency,
            referenceType:   $model->reference_type,
            referenceId:     $model->reference_id,
            performedBy:     $model->performed_by,
            movementDate:    $model->movement_date,
            notes:           $model->notes,
            metadata:        isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:          $model->status,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
