<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class TenantPlanRevisionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $values = $this->resource instanceof DataRecord
            ? $this->resource->toArray()
            : (is_array($this->resource) ? $this->resource : []);

        return array_intersect_key($values, array_flip([
            'id',
            'tenant_plan_id',
            'revision_number',
            'features',
            'limits',
            'price',
            'currency_id',
            'currency',
            'billing_interval',
            'effective_at',
            'plan',
            'total_subscription_count',
            'current_subscription_count',
            'historical_subscription_count',
            'created_at',
        ]));
    }
}
