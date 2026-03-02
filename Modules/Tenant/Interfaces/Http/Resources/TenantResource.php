<?php

declare(strict_types=1);

namespace Modules\Tenant\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenant\Domain\Entities\Tenant;

class TenantResource extends JsonResource
{
    /** @var Tenant */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'status' => $this->resource->status,
            'domain' => $this->resource->domain,
            'plan_code' => $this->resource->planCode,
            'currency' => $this->resource->currency,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
