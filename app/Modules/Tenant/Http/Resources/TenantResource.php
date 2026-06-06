<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\DTOs\TenantValueData;

final class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof TenantValueData) {
            return [
                'id' => $this->resource->id,
                'uuid' => $this->resource->uuid,
                'code' => $this->resource->code,
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'logo_path' => $this->resource->logoPath,
                'cross_org_transactions' => $this->resource->crossOrgTransactions,
                'tenant_plan_id' => $this->resource->tenantPlanId,
                'currency_id' => $this->resource->currencyId,
                'status' => $this->resource->status,
                'trial_ends_at' => $this->resource->trialEndsAt,
                'subscription_ends_at' => $this->resource->subscriptionEndsAt,
                'is_active' => $this->resource->isActive,
                'is_isolated' => $this->resource->isIsolated,
                'isolation_key' => $this->resource->isolationKey,
                'configuration_scope' => $this->resource->configurationScope,
                'metadata' => $this->resource->metadata,
            ];
        }

        if ($this->resource instanceof DataRecord) {
            return $this->resource->toArray();
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        return [];
    }
}
