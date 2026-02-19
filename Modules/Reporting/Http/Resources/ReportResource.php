<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'format' => $this->format->value,
            'format_label' => $this->format->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'query_config' => $this->query_config,
            'fields' => $this->fields,
            'filters' => $this->filters,
            'grouping' => $this->grouping,
            'sorting' => $this->sorting,
            'aggregations' => $this->aggregations,
            'metadata' => $this->metadata,
            'is_template' => $this->is_template,
            'is_shared' => $this->is_shared,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'schedules_count' => $this->whenCounted('schedules'),
            'executions' => $this->whenLoaded('executions', fn () => $this->executions),
        ];
    }
}
