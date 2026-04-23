<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComboItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'combo_product_id' => $this->getComboProductId(),
            'component_product_id' => $this->getComponentProductId(),
            'component_variant_id' => $this->getComponentVariantId(),
            'quantity' => $this->getQuantity(),
            'uom_id' => $this->getUomId(),
            'metadata' => $this->getMetadata(),
            'sort_order' => $this->getSortOrder(),
            'is_optional' => $this->isOptional(),
            'notes' => $this->getNotes(),
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
        ];
    }
}
