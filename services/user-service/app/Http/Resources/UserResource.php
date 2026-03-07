<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'tenant_id'     => $this->tenant_id,
            'keycloak_id'   => $this->keycloak_id,
            'name'          => $this->name,
            'email'         => $this->email,
            'username'      => $this->username,
            'role'          => $this->role,
            'status'        => $this->status,
            'profile'       => $this->profile ?? [],
            'permissions'   => $this->permissions ?? [],
            'metadata'      => $this->when($this->shouldExposeMetadata($request), $this->metadata ?? []),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }

    private function shouldExposeMetadata(Request $request): bool
    {
        $roles = $request->attributes->get('jwt_claims')?->realm_access?->roles ?? [];

        return in_array('admin', (array) $roles, true)
            || in_array('super-admin', (array) $roles, true);
    }
}
