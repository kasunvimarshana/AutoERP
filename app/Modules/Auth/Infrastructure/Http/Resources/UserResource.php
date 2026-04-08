<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @mixin \Modules\Auth\Infrastructure\Persistence\Eloquent\Models\UserModel
 */
final class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'uuid'               => $this->uuid,
            'tenant_id'          => $this->tenant_id,
            'name'               => $this->name,
            'email'              => $this->email,
            'status'             => $this->status,
            'phone'              => $this->phone,
            'avatar_path'        => $this->avatar_path,
            'preferences'        => $this->preferences,
            'locale'             => $this->locale,
            'timezone'           => $this->timezone,
            'email_verified_at'  => $this->email_verified_at?->toIso8601String(),
            'last_login_at'      => $this->last_login_at?->toIso8601String(),
            'two_factor_enabled' => $this->two_factor_enabled,
            'metadata'           => $this->metadata,
            'roles'              => RoleResource::collection($this->whenLoaded('roles')),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
