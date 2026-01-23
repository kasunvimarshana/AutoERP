<?php

declare(strict_types=1);

namespace Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Authentication Resource
 * 
 * Transform authentication response data
 */
class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this->resource['user']->id,
                'name' => $this->resource['user']->name,
                'email' => $this->resource['user']->email,
                'email_verified_at' => $this->resource['user']->email_verified_at,
                'created_at' => $this->resource['user']->created_at,
                'roles' => $this->when(
                    $this->resource['user']->relationLoaded('roles'),
                    $this->resource['user']->roles->pluck('name')
                ),
                'permissions' => $this->when(
                    $this->resource['user']->relationLoaded('permissions'),
                    $this->resource['user']->getAllPermissions()->pluck('name')
                ),
            ],
            'token' => $this->resource['token'] ?? null,
        ];
    }
}
