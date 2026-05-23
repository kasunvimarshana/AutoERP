<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'group_id' => $this->group_id,
            'key' => $this->key,
            'value' => $this->value,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
