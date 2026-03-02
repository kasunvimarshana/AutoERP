<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Domain\Entities\WorkflowInstance;

class WorkflowInstanceResource extends JsonResource
{
    /** @var WorkflowInstance */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'workflow_definition_id' => $this->resource->workflowDefinitionId,
            'entity_type' => $this->resource->entityType,
            'entity_id' => $this->resource->entityId,
            'current_state_id' => $this->resource->currentStateId,
            'status' => $this->resource->status,
            'started_at' => $this->resource->startedAt,
            'completed_at' => $this->resource->completedAt,
            'started_by_user_id' => $this->resource->startedByUserId,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
