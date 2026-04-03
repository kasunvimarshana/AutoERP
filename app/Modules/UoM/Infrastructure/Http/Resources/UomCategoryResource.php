<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UomCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'name'        => $this->getName(),
            'code'        => $this->getCode(),
            'description' => $this->getDescription(),
            'is_active'   => $this->isActive(),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
