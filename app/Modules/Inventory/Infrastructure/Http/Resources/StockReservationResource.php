<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReservationResource extends JsonResource
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
            'location_id' => $this->getLocationId(),
            'quantity' => $this->getQuantity(),
            'reserved_for_type' => $this->getReservedForType(),
            'reserved_for_id' => $this->getReservedForId(),
            'expires_at' => $this->getExpiresAt(),
        ];
    }
}
