<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource — wraps authenticated user data for API responses.
 *
 * @mixin \App\Infrastructure\Persistence\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @param  Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'tenant_id'  => $this->tenant_id,
            'is_active'  => $this->is_active,
            'roles'      => $this->getRoleNames(),
            'permissions'=> $this->getAllPermissionNames(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
