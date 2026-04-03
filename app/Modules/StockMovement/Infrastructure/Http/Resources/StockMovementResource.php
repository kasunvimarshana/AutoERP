<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'reference_number' => $this->getReferenceNumber(),
            'movement_type'    => $this->getMovementType(),
            'status'           => $this->getStatus(),
            'product_id'       => $this->getProductId(),
            'variation_id'     => $this->getVariationId(),
            'from_location_id' => $this->getFromLocationId(),
            'to_location_id'   => $this->getToLocationId(),
            'batch_id'         => $this->getBatchId(),
            'serial_number_id' => $this->getSerialNumberId(),
            'uom_id'           => $this->getUomId(),
            'quantity'         => $this->getQuantity(),
            'unit_cost'        => $this->getUnitCost(),
            'currency'         => $this->getCurrency(),
            'reference_type'   => $this->getReferenceType(),
            'reference_id'     => $this->getReferenceId(),
            'performed_by'     => $this->getPerformedBy(),
            'movement_date'    => $this->getMovementDate()?->format('Y-m-d H:i:s'),
            'notes'            => $this->getNotes(),
            'metadata'         => $this->getMetadata()->toArray(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}
