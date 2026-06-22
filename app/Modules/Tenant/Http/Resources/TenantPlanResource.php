<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class TenantPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $values = $this->resource instanceof DataRecord
            ? $this->resource->toArray()
            : (is_array($this->resource) ? $this->resource : []);

        return array_intersect_key($values, array_flip([
            'id',
            'name',
            'slug',
            'features',
            'limits',
            'price',
            'currency_id',
            'currency',
            'billing_interval',
            'is_active',
            'row_version',
            'created_at',
            'updated_at',
        ]));
    }
}
