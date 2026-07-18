<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Services\Plans\TenantPlanSchema;

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
            'assigned_subscription_count',
            'current_subscription_count',
            'historical_subscription_count',
            'current_revision',
            'latest_revision',
            'created_at',
            'updated_at',
        ]));

        $resource['current_revision'] = $this->sanitizeRevision($resource['current_revision'] ?? null);
        $resource['latest_revision'] = $this->sanitizeRevision($resource['latest_revision'] ?? null);

        $current = is_array($resource['current_revision'] ?? null)
            ? $resource['current_revision']
            : null;
        if ($current !== null) {
            $resource['features'] = $current['features'] ?? null;
            $resource['limits'] = $current['limits'] ?? null;
            $resource['price'] = $current['price'] ?? null;
            $resource['currency_id'] = $current['currency_id'] ?? null;
            $resource['currency'] = $current['currency'] ?? null;
            $resource['billing_interval'] = $current['billing_interval'] ?? null;
        }

        return $resource;
    }

    /** @return array<string, mixed>|null */
    private function sanitizeRevision(mixed $revision): ?array
    {
        if (! is_array($revision)) {
            return null;
        }

        $revision['features'] = app(TenantPlanSchema::class)
            ->normalizePersistedFeatures($revision['features'] ?? []);

        return $revision;
    }
}
