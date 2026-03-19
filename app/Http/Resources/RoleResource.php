<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'guard_name'  => $this->guard_name,
            'permissions' => $this->whenLoaded('permissions', fn (): array => $this->permissions
                ->map(fn ($p): array => ['id' => $p->id, 'name' => $p->name])
                ->values()
                ->all()
            ),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
