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
            'base_currency_id',
            'status',
            'status_reason',
            'status_changed_at',
            'activated_at',
            'suspended_at',
            'archived_at',
            'row_version',
            'base_currency',
            'current_subscription',
            'onboarding',
            'primary_domain',
            'created_at',
            'updated_at',
        ]));
        $resource['has_logo'] = isset($values['logo_object_key'])
            && is_string($values['logo_object_key'])
            && trim($values['logo_object_key']) !== '';

        return $resource;
    }
}
