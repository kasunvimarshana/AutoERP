<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowInstanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'workflow' => $this->whenLoaded('workflow', fn () => new WorkflowResource($this->workflow)),
            'status' => $this->status,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'context' => $this->context,
            'current_step_id' => $this->current_step_id,
            'current_step' => $this->whenLoaded('currentStep', fn () => new WorkflowStepResource($this->currentStep)),
            'started_by' => $this->started_by,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'failed_at' => $this->failed_at,
            'cancelled_at' => $this->cancelled_at,
            'error_message' => $this->error_message,
            'metadata' => $this->metadata,
            'instance_steps' => WorkflowInstanceStepResource::collection($this->whenLoaded('instanceSteps')),
            'approvals' => ApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
