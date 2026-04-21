<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Domain\Entities\SalesOrder;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SalesOrder $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'customer_id' => $entity->getCustomerId(),
            'org_unit_id' => $entity->getOrgUnitId(),
            'warehouse_id' => $entity->getWarehouseId(),
            'so_number' => $entity->getSoNumber(),
            'status' => $entity->getStatus(),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'order_date' => $entity->getOrderDate()->format('c'),
            'requested_delivery_date' => $entity->getRequestedDeliveryDate()?->format('c'),
            'price_list_id' => $entity->getPriceListId(),
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
        ];
    }
}
