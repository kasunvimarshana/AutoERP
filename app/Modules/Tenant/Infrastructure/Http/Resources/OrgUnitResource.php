<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @mixin \Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel
 */
final class OrgUnitResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'tenant_id'   => $this->tenant_id,
            'parent_id'   => $this->parent_id,
            'name'        => $this->name,
            'code'        => $this->code,
            'type'        => $this->type,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'sort_order'  => $this->sort_order,
            'metadata'    => $this->metadata,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
