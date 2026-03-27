<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $image = $this->getImage();
        $children = $this->getChildren();

        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'name'        => $this->getName(),
            'slug'        => $this->getSlug(),
            'description' => $this->getDescription(),
            'parent_id'   => $this->getParentId(),
            'depth'       => $this->getDepth(),
            'path'        => $this->getPath(),
            'status'      => $this->getStatus(),
            'attributes'  => $this->getAttributes(),
            'metadata'    => $this->getMetadata(),
            'image'       => $image ? new CategoryImageResource($image) : null,
            'children'    => $children->isNotEmpty() ? CategoryResource::collection($children) : [],
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
