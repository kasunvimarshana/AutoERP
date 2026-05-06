<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Infrastructure\Http\Resources\PurchaseOrderLineResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PurchaseOrder $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'org_unit_id' => $entity->getOrgUnitId(),
            'warehouse_id' => $entity->getWarehouseId(),
            'po_number' => $entity->getPoNumber(),
            'status' => $entity->getStatus(),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'order_date' => $entity->getOrderDate()->format('c'),
            'expected_date' => $entity->getExpectedDate()?->format('c'),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'discount_total' => $entity->getDiscountTotal(),
            'grand_total' => $entity->getGrandTotal(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_by' => $entity->getCreatedBy(),
            'approved_by' => $entity->getApprovedBy(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
            'lines' => PurchaseOrderLineResource::collection($entity->getLines()),
        ];
    }
}
