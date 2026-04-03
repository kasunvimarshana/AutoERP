<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UomConversionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'from_uom_id' => $this->getFromUomId(),
            'to_uom_id'   => $this->getToUomId(),
            'factor'      => $this->getFactor(),
            'is_active'   => $this->isActive(),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
