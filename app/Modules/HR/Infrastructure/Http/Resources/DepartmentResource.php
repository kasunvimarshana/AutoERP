<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\Department;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Department $dept */
        $dept = $this->resource;
        return [
            'id'          => $dept->getId(),
            'tenant_id'   => $dept->getTenantId(),
            'name'        => $dept->getName(),
            'code'        => $dept->getCode(),
            'description' => $dept->getDescription(),
            'manager_id'  => $dept->getManagerId(),
            'parent_id'   => $dept->getParentId(),
            'is_active'   => $dept->isActive(),
            'created_at'  => $dept->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'  => $dept->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
