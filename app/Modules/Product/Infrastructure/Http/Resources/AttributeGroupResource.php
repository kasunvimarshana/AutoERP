<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'name' => $this->getName(),
            'code' => $this->getCode(),
            'description' => $this->getDescription(),
            'sort_order' => $this->getSortOrder(),
            'is_active' => $this->isActive(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
        ];
    }
}
