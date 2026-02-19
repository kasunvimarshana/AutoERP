<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'sequence' => $this->sequence,
            'config' => $this->config,
            'action_config' => $this->action_config,
            'approval_config' => $this->approval_config,
            'condition_config' => $this->condition_config,
            'timeout_seconds' => $this->timeout_seconds,
            'retry_count' => $this->retry_count,
            'is_required' => $this->is_required,
            'metadata' => $this->metadata,
            'conditions' => WorkflowConditionResource::collection($this->whenLoaded('conditions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
