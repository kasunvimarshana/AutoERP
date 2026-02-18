<?php

namespace Modules\IAM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'permission_name' => $this->name,
            'guard_context' => $this->guard_name,
            'description' => $this->description,
            'scope' => $this->buildPermissionScope(),
            'system_managed' => $this->is_system,
            'tenant_scoped' => !is_null($this->tenant_id),
            'tenant_reference' => $this->when(
                $this->tenant_id,
                fn() => [
                    'tenant_id' => $this->tenant_id,
                    'tenant_name' => $this->tenant?->name,
                ]
            ),
            'roles_granting_permission' => $this->when(
                $this->relationLoaded('roles'),
                fn() => [
                    'count' => $this->roles->count(),
                    'role_names' => $this->roles->pluck('name')->toArray(),
                ]
            ),
        ];
    }

    private function buildPermissionScope(): array
    {
        return [
            'resource_type' => $this->resource,
            'action_type' => $this->action,
            'full_scope' => "{$this->resource}:{$this->action}",
        ];
    }
}
