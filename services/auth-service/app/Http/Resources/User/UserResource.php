<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'tenant_id'   => $this->tenant_id,
            'org_id'      => $this->org_id,
            'timezone'    => $this->timezone,
            'locale'      => $this->locale,
            'is_active'   => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'roles'       => $this->whenLoaded('roles', fn() => $this->getRoleNames()),
            'permissions' => $this->when(
                $request->boolean('with_permissions'),
                fn() => $this->getAllPermissions()->pluck('name')
            ),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
