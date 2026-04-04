<?php
namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

class UnitOfMeasureResource extends JsonResource
{
    public function __construct(private readonly UnitOfMeasure $entity)
    {
        parent::__construct($entity);
    }

    public function toArray($request): array
    {
        return [
            'id'                => $this->entity->id,
            'tenant_id'         => $this->entity->tenantId,
            'category_id'       => $this->entity->categoryId,
            'name'              => $this->entity->name,
            'symbol'            => $this->entity->symbol,
            'conversion_factor' => $this->entity->conversionFactor,
            'is_base'           => $this->entity->isBase,
            'is_active'         => $this->entity->isActive,
        ];
    }
}
