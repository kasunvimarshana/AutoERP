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
            'revision_number',
            'operation',
            'tenant_plan_revision_id',
            'supersedes_subscription_id',
            'contract_status',
            'effective_status',
            'starts_at',
            'trial_ends_at',
            'ends_at',
            'change_reason',
            'plan_name',
            'plan_slug',
            'plan_features',
            'plan_limits',
            'price',
            'currency_code',
            'currency_symbol',
            'billing_interval',
            'current_state',
            'current_state_reason',
            'current_state_changed_at',
            'row_version',
            'assigned_at',
            'assigned_by',
            'revision',
            'created_at',
        ]));
    }
}
