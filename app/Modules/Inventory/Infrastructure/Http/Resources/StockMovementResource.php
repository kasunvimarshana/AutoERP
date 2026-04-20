<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'product_id' => $this->getProductId(),
            'variant_id' => $this->getVariantId(),
            'batch_id' => $this->getBatchId(),
            'serial_id' => $this->getSerialId(),
            'from_location_id' => $this->getFromLocationId(),
            'to_location_id' => $this->getToLocationId(),
            'movement_type' => $this->getMovementType(),
            'reference_type' => $this->getReferenceType(),
            'reference_id' => $this->getReferenceId(),
            'uom_id' => $this->getUomId(),
            'quantity' => $this->getQuantity(),
            'unit_cost' => $this->getUnitCost(),
            'performed_by' => $this->getPerformedBy(),
            'performed_at' => $this->getPerformedAt()?->format('c'),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
        ];
    }
}
