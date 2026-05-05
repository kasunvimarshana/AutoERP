<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\PurchaseReturnLine;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;

class EloquentPurchaseReturnLineRepository extends EloquentRepository implements PurchaseReturnLineRepositoryInterface
{
    public function __construct(PurchaseReturnLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseReturnLineModel $m): PurchaseReturnLine => $this->mapToDomain($m));
    }

    public function save(PurchaseReturnLine $entity): PurchaseReturnLine
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'purchase_return_id' => $entity->getPurchaseReturnId(),
            'original_grn_line_id' => $entity->getOriginalGrnLineId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'batch_id' => $entity->getBatchId(),
            'serial_id' => $entity->getSerialId(),
            'from_location_id' => $entity->getFromLocationId(),
            'uom_id' => $entity->getUomId(),
            'return_qty' => $entity->getReturnQty(),
            'unit_cost' => $entity->getUnitCost(),
            'condition' => $entity->getCondition(),
            'disposition' => $entity->getDisposition(),
            'restocking_fee' => $entity->getRestockingFee(),
            'quality_check_notes' => $entity->getQualityCheckNotes(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?PurchaseReturnLine
    {
        return parent::find($id, $columns);
    }

    public function findByPurchaseReturnId(int $tenantId, int $purchaseReturnId): Collection
    {
        $models = $this->newScopedQuery()
            ->where('tenant_id', $tenantId)
            ->where('purchase_return_id', $purchaseReturnId)
            ->get();

        return $this->toDomainCollection($models);
    }

    private function mapToDomain(PurchaseReturnLineModel $m): PurchaseReturnLine
    {
        return new PurchaseReturnLine(
            tenantId: (int) $m->tenant_id,
            purchaseReturnId: (int) $m->purchase_return_id,
            productId: (int) $m->product_id,
            fromLocationId: (int) $m->from_location_id,
            uomId: (int) $m->uom_id,
            returnQty: (string) $m->return_qty,
            unitCost: (string) $m->unit_cost,
            condition: (string) $m->condition,
            disposition: (string) $m->disposition,
            restockingFee: (string) $m->restocking_fee,
            originalGrnLineId: $m->original_grn_line_id !== null ? (int) $m->original_grn_line_id : null,
            variantId: $m->variant_id !== null ? (int) $m->variant_id : null,
            batchId: $m->batch_id !== null ? (int) $m->batch_id : null,
            serialId: $m->serial_id !== null ? (int) $m->serial_id : null,
            qualityCheckNotes: $m->quality_check_notes !== null ? (string) $m->quality_check_notes : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
