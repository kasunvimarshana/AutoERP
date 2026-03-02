<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Domain\Entities\WorkflowState;

class WorkflowStateResource extends JsonResource
{
    /** @var WorkflowState */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'workflow_definition_id' => $this->resource->workflowDefinitionId,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'is_initial' => $this->resource->isInitial,
            'is_final' => $this->resource->isFinal,
            'sort_order' => $this->resource->sortOrder,
        ];
    }
}
