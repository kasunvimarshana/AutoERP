<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'tenant_id'         => $this->tenant_id,
            'organization_id'   => $this->organization_id,
            'branch_id'         => $this->branch_id,
            'status'            => $this->status,
            'locale'            => $this->locale,
            'timezone'          => $this->timezone,
            'roles'             => $this->getRoleNames(),
            'permissions'       => $this->getAllPermissions()->pluck('name'),
            'last_login_at'     => $this->last_login_at?->toIso8601String(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
