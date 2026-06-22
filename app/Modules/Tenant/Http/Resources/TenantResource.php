<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class TenantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $values = $this->resource instanceof DataRecord
            ? $this->resource->toArray()
            : (is_array($this->resource) ? $this->resource : []);

        $resource = array_intersect_key($values, array_flip([
            'id',
            'uuid',
            'code',
            'name',
            'slug',
            'cross_org_transactions',
            'tenant_plan_id',
            'base_currency_id',
            'status',
            'status_reason',
            'activated_at',
            'suspended_at',
            'archived_at',
            'trial_ends_at',
            'subscription_ends_at',
            'row_version',
            'plan',
            'base_currency',
            'created_at',
            'updated_at',
        ]));
        $resource['has_logo'] = isset($values['logo_path'])
            && is_string($values['logo_path'])
            && trim($values['logo_path']) !== '';

        return $resource;
    }
}
