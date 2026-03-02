<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Domain\Entities\WorkflowInstanceLog;

class WorkflowInstanceLogResource extends JsonResource
{
    /** @var WorkflowInstanceLog */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'workflow_instance_id' => $this->resource->workflowInstanceId,
            'from_state_id' => $this->resource->fromStateId,
            'to_state_id' => $this->resource->toStateId,
            'transition_id' => $this->resource->transitionId,
            'comment' => $this->resource->comment,
            'actor_user_id' => $this->resource->actorUserId,
            'acted_at' => $this->resource->actedAt,
            'created_at' => $this->resource->createdAt,
        ];
    }
}
