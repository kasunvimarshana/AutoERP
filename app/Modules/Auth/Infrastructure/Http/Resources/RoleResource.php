<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Domain\Entities\Role;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Role $role */
        $role = $this->resource;

        return [
            'id'          => $role->id,
            'tenant_id'   => $role->tenantId,
            'name'        => $role->name,
            'guard'       => $role->guard,
            'permissions' => $role->permissions,
            'created_at'  => $role->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at'  => $role->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
