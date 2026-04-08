<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class OrganizationUnitResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'parent_id'       => $this->parent_id,
            'code'            => $this->code,
            'name'            => $this->name,
            'type'            => $this->type,
            'description'     => $this->description,
            'manager_user_id' => $this->manager_user_id,
            'is_active'       => $this->is_active,
            'sort_order'      => $this->sort_order,
            'children'        => OrganizationUnitResource::collection($this->whenLoaded('children')),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
