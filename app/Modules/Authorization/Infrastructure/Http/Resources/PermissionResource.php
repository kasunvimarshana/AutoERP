<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authorization\Domain\Entities\Permission;

class PermissionResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Permission $permission */
        $permission = $this->resource;
        return [
            'id' => $permission->getId(),
            'name' => $permission->getName(),
            'slug' => $permission->getSlug(),
            'module' => $permission->getModule(),
            'description' => $permission->getDescription(),
            'created_at' => $permission->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $permission->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
