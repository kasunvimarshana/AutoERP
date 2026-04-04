<?php
namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\UoM\Domain\Entities\ProductUomSetting;

class ProductUomSettingResource extends JsonResource
{
    public function __construct(private readonly ProductUomSetting $entity)
    {
        parent::__construct($entity);
    }

    public function toArray($request): array
    {
        return [
            'id'               => $this->entity->id,
            'product_id'       => $this->entity->productId,
            'base_uom_id'      => $this->entity->baseUomId,
            'purchase_uom_id'  => $this->entity->purchaseUomId,
            'sales_uom_id'     => $this->entity->salesUomId,
            'inventory_uom_id' => $this->entity->inventoryUomId,
            'purchase_factor'  => $this->entity->purchaseFactor,
            'sales_factor'     => $this->entity->salesFactor,
            'inventory_factor' => $this->entity->inventoryFactor,
        ];
    }
}
