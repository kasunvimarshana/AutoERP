<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantDomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'domain' => $this->domain,
            'is_primary' => $this->is_primary,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
