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

        $resource = array_intersect_key($values, array_flip([
            'id',
            'name',
            'slug',
            'is_active',
            'row_version',
            'revisions_count',
            'total_subscription_count',
            'current_subscription_count',
            'historical_subscription_count',
            'latest_revision',
            'created_at',
            'updated_at',
        ]));

        $latest = is_array($resource['latest_revision'] ?? null)
            ? $resource['latest_revision']
            : null;
        if ($latest !== null) {
            $resource['features'] = $latest['features'] ?? null;
            $resource['limits'] = $latest['limits'] ?? null;
            $resource['price'] = $latest['price'] ?? null;
            $resource['currency_id'] = $latest['currency_id'] ?? null;
            $resource['currency'] = $latest['currency'] ?? null;
            $resource['billing_interval'] = $latest['billing_interval'] ?? null;
        }

        return $resource;
    }
}
