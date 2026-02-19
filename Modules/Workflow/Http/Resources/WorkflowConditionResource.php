<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowConditionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'step_id' => $this->step_id,
            'type' => $this->type,
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
            'next_step_id' => $this->next_step_id,
            'is_default' => $this->is_default,
            'sequence' => $this->sequence,
            'metadata' => $this->metadata,
        ];
    }
}
