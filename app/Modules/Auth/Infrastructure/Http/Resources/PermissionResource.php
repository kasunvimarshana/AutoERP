<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @mixin \Modules\Auth\Infrastructure\Persistence\Eloquent\Models\PermissionModel
 */
final class PermissionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'module'      => $this->module,
            'guard_name'  => $this->guard_name,
            'metadata'    => $this->metadata,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
