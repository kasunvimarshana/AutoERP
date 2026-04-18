<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'parent_id' => $this->getParentId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'code' => $this->getCode(),
            'path' => $this->getPath(),
            'depth' => $this->getDepth(),
            'is_active' => $this->isActive(),
            'website' => $this->getWebsite(),
            'description' => $this->getDescription(),
            'attributes' => $this->getAttributes(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
