<?php
namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\UoM\Domain\Entities\UomConversion;

class UomConversionResource extends JsonResource
{
    public function __construct(private readonly UomConversion $entity)
    {
        parent::__construct($entity);
    }

    public function toArray($request): array
    {
        return [
            'id'          => $this->entity->id,
            'from_uom_id' => $this->entity->fromUomId,
            'to_uom_id'   => $this->entity->toUomId,
            'factor'      => $this->entity->factor,
            'product_id'  => $this->entity->productId,
        ];
    }
}
