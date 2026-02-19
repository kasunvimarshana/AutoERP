<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workflow_instance_id' => $this->workflow_instance_id,
            'workflow_step_id' => $this->workflow_step_id,
            'approver_id' => $this->approver_id,
            'approver' => $this->whenLoaded('approver'),
            'delegated_to' => $this->delegated_to,
            'status' => $this->status,
            'priority' => $this->priority,
            'subject' => $this->subject,
            'description' => $this->description,
            'comments' => $this->comments,
            'decision_data' => $this->decision_data,
            'requested_at' => $this->requested_at,
            'responded_at' => $this->responded_at,
            'escalated_at' => $this->escalated_at,
            'escalation_level' => $this->escalation_level,
            'due_at' => $this->due_at,
            'is_overdue' => $this->isOverdue(),
            'metadata' => $this->metadata,
            'instance' => $this->whenLoaded('instance', fn () => new WorkflowInstanceResource($this->instance)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
