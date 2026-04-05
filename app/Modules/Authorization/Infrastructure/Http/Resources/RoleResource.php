<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authorization\Domain\Entities\Role;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Role $role */
        $role = $this->resource;
        return [
            'id' => $role->getId(),
            'tenant_id' => $role->getTenantId(),
            'name' => $role->getName(),
            'slug' => $role->getSlug(),
            'description' => $role->getDescription(),
            'created_at' => $role->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $role->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
