<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowInstanceStepResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workflow_instance_id' => $this->workflow_instance_id,
            'workflow_step_id' => $this->workflow_step_id,
            'step' => $this->whenLoaded('step', fn () => new WorkflowStepResource($this->step)),
            'status' => $this->status,
            'input_data' => $this->input_data,
            'output_data' => $this->output_data,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'failed_at' => $this->failed_at,
            'error_message' => $this->error_message,
            'retry_count' => $this->retry_count,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
