<?php

declare(strict_types=1);

namespace Modules\Organisation\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Organisation\Domain\Entities\Organisation;

class OrganisationResource extends JsonResource
{
    /** @var Organisation */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'parent_id' => $this->resource->parentId,
            'type' => $this->resource->type,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'description' => $this->resource->description,
            'status' => $this->resource->status,
            'meta' => $this->resource->meta,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
