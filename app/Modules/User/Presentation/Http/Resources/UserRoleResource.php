<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
        ];
    }
}
