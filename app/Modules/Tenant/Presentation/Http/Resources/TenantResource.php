<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_path' => $this->logo_path,
            'cross_org_transactions' => $this->cross_org_transactions,
            'tenant_plan_id' => $this->tenant_plan_id,
            'currency_id' => $this->currency_id,
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at,
            'subscription_ends_at' => $this->subscription_ends_at,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
