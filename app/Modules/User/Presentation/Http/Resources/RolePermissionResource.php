<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolePermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'role_id' => $this->role_id,
            'permission_id' => $this->permission_id,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
        ];
    }
}
