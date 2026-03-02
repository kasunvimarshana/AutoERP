<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Domain\Entities\WorkflowDefinition;

class WorkflowDefinitionResource extends JsonResource
{
    /** @var WorkflowDefinition */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'entity_type' => $this->resource->entityType,
            'status' => $this->resource->status,
            'is_active' => $this->resource->isActive,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
