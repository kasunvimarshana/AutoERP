<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\GrnLine;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnLineRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;

class EloquentGrnLineRepository extends EloquentRepository implements GrnLineRepositoryInterface
{
    public function __construct(GrnLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (GrnLineModel $m): GrnLine => $this->mapToDomain($m));
    }

    public function save(GrnLine $entity): GrnLine
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'grn_header_id' => $entity->getGrnHeaderId(),
            'purchase_order_line_id' => $entity->getPurchaseOrderLineId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'batch_id' => $entity->getBatchId(),
            'serial_id' => $entity->getSerialId(),
            'location_id' => $entity->getLocationId(),
            'uom_id' => $entity->getUomId(),
            'expected_qty' => $entity->getExpectedQty(),
            'received_qty' => $entity->getReceivedQty(),
            'rejected_qty' => $entity->getRejectedQty(),
            'unit_cost' => $entity->getUnitCost(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?GrnLine
    {
        return parent::find($id, $columns);
    }

    public function findByGrnHeaderId(int $tenantId, int $grnHeaderId): Collection
    {
        $models = $this->newScopedQuery()
            ->where('tenant_id', $tenantId)
            ->where('grn_header_id', $grnHeaderId)
            ->get();

        return $this->toDomainCollection($models);
    }

    private function mapToDomain(GrnLineModel $m): GrnLine
    {
        return new GrnLine(
            tenantId: (int) $m->tenant_id,
            grnHeaderId: (int) $m->grn_header_id,
            productId: (int) $m->product_id,
            locationId: (int) $m->location_id,
            uomId: (int) $m->uom_id,
            receivedQty: (string) $m->received_qty,
            unitCost: (string) $m->unit_cost,
            expectedQty: (string) $m->expected_qty,
            rejectedQty: (string) $m->rejected_qty,
            purchaseOrderLineId: $m->purchase_order_line_id !== null ? (int) $m->purchase_order_line_id : null,
            variantId: $m->variant_id !== null ? (int) $m->variant_id : null,
            batchId: $m->batch_id !== null ? (int) $m->batch_id : null,
            serialId: $m->serial_id !== null ? (int) $m->serial_id : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
