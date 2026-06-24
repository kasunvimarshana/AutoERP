<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class TenantSubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $values = $this->resource instanceof DataRecord
            ? $this->resource->toArray()
            : (is_array($this->resource) ? $this->resource : []);

        return array_intersect_key($values, array_flip([
            'id',
            'tenant_id',
            'status',
            'starts_at',
            'trial_ends_at',
            'ends_at',
            'cancelled_at',
            'cancellation_reason',
            'row_version',
            'revision',
            'created_at',
            'updated_at',
        ]));
    }
}
