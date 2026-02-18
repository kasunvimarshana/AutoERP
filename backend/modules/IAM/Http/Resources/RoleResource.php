<?php

namespace Modules\IAM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_name' => $this->name,
            'role_identifier' => $this->guard_name,
            'role_description' => $this->description,
            'system_role' => $this->is_system,
            'editable' => !$this->is_system,
            'hierarchy' => $this->buildHierarchyInfo(),
            'tenant_scope' => $this->when(
                $this->tenant_id,
                fn() => [
                    'tenant_id' => $this->tenant_id,
                    'tenant_name' => $this->tenant?->name,
                ]
            ),
            'permission_grants' => PermissionResource::collection($this->whenLoaded('permissions')),
            'users_with_role' => $this->when(
                $this->relationLoaded('users'),
                fn() => [
                    'count' => $this->users->count(),
                    'users' => UserResource::collection($this->whenLoaded('users')),
                ]
            ),
            'subordinate_roles' => $this->when(
                $this->relationLoaded('children'),
                fn() => self::collection($this->children)
            ),
            'metadata' => [
                'created' => $this->created_at?->toIso8601String(),
                'modified' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function buildHierarchyInfo(): array
    {
        $info = [
            'has_parent' => !is_null($this->parent_id),
            'parent_role_id' => $this->parent_id,
        ];

        if ($this->relationLoaded('parent') && $this->parent) {
            $info['parent_role_name'] = $this->parent->name;
        }

        return $info;
    }
}
