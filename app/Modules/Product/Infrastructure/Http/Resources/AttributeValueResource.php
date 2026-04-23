<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'attribute_id' => $this->getAttributeId(),
            'value' => $this->getValue(),
            'sort_order' => $this->getSortOrder(),
            'label' => $this->getLabel(),
            'color_code' => $this->getColorCode(),
            'is_active' => $this->isActive(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
        ];
    }
}
