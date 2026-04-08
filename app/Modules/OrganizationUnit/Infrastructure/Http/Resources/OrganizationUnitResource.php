<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationUnitResource extends JsonResource
{
    public function toArray($request)
    {
        // $resource = $this->resource;
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'name' => $this->getName()->value(),
            'code' => $this->getCode()?->value(),
            'description' => $this->getDescription(),
            'metadata' => $this->getMetadata()->toArray(),
            'parent_id' => $this->getParentId(),
            'children' => OrganizationUnitResource::collection($this->getChildren()),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
