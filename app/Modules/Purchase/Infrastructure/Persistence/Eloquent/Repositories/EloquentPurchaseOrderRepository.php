<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;

class EloquentPurchaseOrderRepository extends EloquentRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrderModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseOrderModel $m): PurchaseOrder => $this->mapToDomain($m));
    }

    public function save(PurchaseOrder $entity): PurchaseOrder
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'org_unit_id' => $entity->getOrgUnitId(),
            'warehouse_id' => $entity->getWarehouseId(),
            'po_number' => $entity->getPoNumber(),
            'status' => $entity->getStatus(),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'order_date' => $entity->getOrderDate()->format('Y-m-d'),
            'expected_date' => $entity->getExpectedDate()?->format('Y-m-d'),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'discount_total' => $entity->getDiscountTotal(),
            'grand_total' => $entity->getGrandTotal(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_by' => $entity->getCreatedBy(),
            'approved_by' => $entity->getApprovedBy(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?PurchaseOrder
    {
        return parent::find($id, $columns);
    }

    private function mapToDomain(PurchaseOrderModel $m): PurchaseOrder
    {
        return new PurchaseOrder(
            tenantId: (int) $m->tenant_id,
            supplierId: (int) $m->supplier_id,
            warehouseId: (int) $m->warehouse_id,
            poNumber: (string) $m->po_number,
            status: (string) $m->status,
            currencyId: (int) $m->currency_id,
            exchangeRate: (string) $m->exchange_rate,
            orderDate: $m->order_date instanceof \DateTimeInterface ? $m->order_date : new \DateTimeImmutable((string) $m->order_date),
            createdBy: (int) $m->created_by,
            orgUnitId: $m->org_unit_id !== null ? (int) $m->org_unit_id : null,
            expectedDate: $m->expected_date !== null
                ? ($m->expected_date instanceof \DateTimeInterface ? $m->expected_date : new \DateTimeImmutable((string) $m->expected_date))
                : null,
            subtotal: (string) $m->subtotal,
            taxTotal: (string) $m->tax_total,
            discountTotal: (string) $m->discount_total,
            grandTotal: (string) $m->grand_total,
            notes: $m->notes !== null ? (string) $m->notes : null,
            metadata: $m->metadata,
            approvedBy: $m->approved_by !== null ? (int) $m->approved_by : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
