<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WidgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'dashboard_id' => $this->dashboard_id,
            'report_id' => $this->report_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'chart_type' => $this->chart_type?->value,
            'chart_type_label' => $this->chart_type?->label(),
            'title' => $this->title,
            'description' => $this->description,
            'configuration' => $this->configuration,
            'data_source' => $this->data_source,
            'refresh_interval' => $this->refresh_interval,
            'order' => $this->order,
            'width' => $this->width,
            'height' => $this->height,
            'position' => [
                'x' => $this->position_x,
                'y' => $this->position_y,
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'dashboard' => $this->whenLoaded('dashboard', fn () => [
                'id' => $this->dashboard->id,
                'name' => $this->dashboard->name,
            ]),
            'report' => $this->whenLoaded('report', fn () => [
                'id' => $this->report->id,
                'name' => $this->report->name,
            ]),
        ];
    }
}
