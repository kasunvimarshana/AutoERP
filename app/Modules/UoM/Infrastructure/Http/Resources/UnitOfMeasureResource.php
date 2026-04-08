<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitOfMeasureResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->getId(),
            'tenant_id'       => $this->getTenantId(),
            'uom_category_id' => $this->getUomCategoryId(),
            'name'            => $this->getName(),
            'code'            => $this->getCode(),
            'symbol'          => $this->getSymbol(),
            'is_base_unit'    => $this->isBaseUnit(),
            'factor'          => $this->getFactor(),
            'description'     => $this->getDescription(),
            'is_active'       => $this->isActive(),
            'created_at'      => $this->getCreatedAt()->format('c'),
            'updated_at'      => $this->getUpdatedAt()->format('c'),
        ];
    }
}
