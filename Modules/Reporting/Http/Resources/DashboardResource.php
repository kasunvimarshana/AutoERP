<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
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
            'layout' => $this->layout,
            'is_default' => $this->is_default,
            'is_shared' => $this->is_shared,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'widgets' => $this->whenLoaded('widgets', fn () => WidgetResource::collection($this->widgets)
            ),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
        ];
    }
}
