<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'features' => $this->features,
            'limits' => $this->limits,
            'price' => $this->price,
            'currency_id' => $this->currency_id,
            'billing_interval' => $this->billing_interval,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
