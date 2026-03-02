<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Domain\Entities\WorkflowTransition;

class WorkflowTransitionResource extends JsonResource
{
    /** @var WorkflowTransition */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'workflow_definition_id' => $this->resource->workflowDefinitionId,
            'from_state_id' => $this->resource->fromStateId,
            'to_state_id' => $this->resource->toStateId,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'requires_comment' => $this->resource->requiresComment,
        ];
    }
}
