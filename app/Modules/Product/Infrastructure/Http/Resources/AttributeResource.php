<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'group_id' => $this->getGroupId(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'is_required' => $this->isRequired(),
            'code' => $this->getCode(),
            'description' => $this->getDescription(),
            'sort_order' => $this->getSortOrder(),
            'is_active' => $this->isActive(),
            'is_filterable' => $this->isFilterable(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
        ];
    }
}
