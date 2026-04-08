<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'name'        => $this->getName()->value(),
            'code'        => $this->getCode()?->value(),
            'description' => $this->getDescription(),
            'manager_id'  => $this->getManagerId(),
            'parent_id'   => $this->getParentId(),
            'lft'         => $this->getLft(),
            'rgt'         => $this->getRgt(),
            'metadata'    => $this->getMetadata()->toArray(),
            'is_active'   => $this->isActive(),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
