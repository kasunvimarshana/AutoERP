<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\PurchaseOrderLine;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;

class EloquentPurchaseOrderLineRepository extends EloquentRepository implements PurchaseOrderLineRepositoryInterface
{
    public function __construct(PurchaseOrderLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseOrderLineModel $m): PurchaseOrderLine => $this->mapToDomain($m));
    }

    public function save(PurchaseOrderLine $entity): PurchaseOrderLine
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'purchase_order_id' => $entity->getPurchaseOrderId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'description' => $entity->getDescription(),
            'uom_id' => $entity->getUomId(),
            'ordered_qty' => $entity->getOrderedQty(),
            'received_qty' => $entity->getReceivedQty(),
            'unit_price' => $entity->getUnitPrice(),
            'discount_pct' => $entity->getDiscountPct(),
            'tax_group_id' => $entity->getTaxGroupId(),
            'account_id' => $entity->getAccountId(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?PurchaseOrderLine
    {
        return parent::find($id, $columns);
    }

    public function findByPurchaseOrderId(int $tenantId, int $purchaseOrderId): Collection
    {
        $models = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('purchase_order_id', $purchaseOrderId)
            ->get();

        return $this->toDomainCollection($models);
    }

    private function mapToDomain(PurchaseOrderLineModel $m): PurchaseOrderLine
    {
        return new PurchaseOrderLine(
            tenantId: (int) $m->tenant_id,
            purchaseOrderId: (int) $m->purchase_order_id,
            productId: (int) $m->product_id,
            uomId: (int) $m->uom_id,
            orderedQty: (string) $m->ordered_qty,
            unitPrice: (string) $m->unit_price,
            receivedQty: (string) $m->received_qty,
            discountPct: (string) $m->discount_pct,
            variantId: $m->variant_id !== null ? (int) $m->variant_id : null,
            description: $m->description !== null ? (string) $m->description : null,
            taxGroupId: $m->tax_group_id !== null ? (int) $m->tax_group_id : null,
            accountId: $m->account_id !== null ? (int) $m->account_id : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
