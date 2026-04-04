<?php
namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\UoM\Domain\Entities\UomCategory;

class UomCategoryResource extends JsonResource
{
    public function __construct(private readonly UomCategory $entity)
    {
        parent::__construct($entity);
    }

    public function toArray($request): array
    {
        return [
            'id'           => $this->entity->id,
            'tenant_id'    => $this->entity->tenantId,
            'name'         => $this->entity->name,
            'measure_type' => $this->entity->measureType,
            'is_active'    => $this->entity->isActive,
            'description'  => $this->entity->description,
        ];
    }
}
