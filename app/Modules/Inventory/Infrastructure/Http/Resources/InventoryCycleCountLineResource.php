<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCycleCountLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->getId(),
            'tenant_id'       => $this->getTenantId(),
            'cycle_count_id'  => $this->getCycleCountId(),
            'product_id'      => $this->getProductId(),
            'variation_id'    => $this->getVariationId(),
            'batch_id'        => $this->getBatchId(),
            'serial_number_id'=> $this->getSerialNumberId(),
            'location_id'     => $this->getLocationId(),
            'expected_qty'    => $this->getExpectedQty(),
            'counted_qty'     => $this->getCountedQty(),
            'variance_qty'    => $this->getVarianceQty(),
            'status'          => $this->getStatus(),
            'counted_at'      => $this->getCountedAt()?->format('c'),
            'counted_by'      => $this->getCountedBy(),
            'notes'           => $this->getNotes(),
            'created_at'      => $this->getCreatedAt()->format('c'),
            'updated_at'      => $this->getUpdatedAt()->format('c'),
        ];
    }
}
