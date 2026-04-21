<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Domain\Entities\Shipment;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Shipment $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'customer_id' => $entity->getCustomerId(),
            'sales_order_id' => $entity->getSalesOrderId(),
            'warehouse_id' => $entity->getWarehouseId(),
            'shipment_number' => $entity->getShipmentNumber(),
            'status' => $entity->getStatus(),
            'shipped_date' => $entity->getShippedDate()?->format('c'),
            'carrier' => $entity->getCarrier(),
            'tracking_number' => $entity->getTrackingNumber(),
            'currency_id' => $entity->getCurrencyId(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
