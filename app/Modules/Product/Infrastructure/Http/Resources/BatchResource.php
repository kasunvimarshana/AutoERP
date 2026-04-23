<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'product_id' => $this->getProductId(),
            'variant_id' => $this->getVariantId(),
            'batch_number' => $this->getBatchNumber(),
            'lot_number' => $this->getLotNumber(),
            'manufactured_date' => $this->getManufacturedDate()?->format('Y-m-d'),
            'expiry_date' => $this->getExpiryDate()?->format('Y-m-d'),
            'quantity' => $this->getQuantity(),
            'status' => $this->getStatus(),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
        ];
    }
}
