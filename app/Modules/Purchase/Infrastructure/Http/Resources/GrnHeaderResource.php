<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\GrnHeader;

class GrnHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var GrnHeader $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'warehouse_id' => $entity->getWarehouseId(),
            'purchase_order_id' => $entity->getPurchaseOrderId(),
            'grn_number' => $entity->getGrnNumber(),
            'status' => $entity->getStatus(),
            'received_date' => $entity->getReceivedDate()->format('c'),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_by' => $entity->getCreatedBy(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
