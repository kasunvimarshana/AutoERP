<?php

declare(strict_types=1);

namespace Modules\Auth\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'role'            => $this->role_id,
            'tenant_id'       => $this->tenant_id,
            'organisation_id' => $this->organisation_id,
            'is_active'       => $this->is_active,
            'last_login_at'   => $this->last_login_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
