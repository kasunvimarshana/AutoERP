<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\StockReorderRule;

class StockReorderRuleResource extends JsonResource
{
    public function __construct(private readonly StockReorderRule $rule)
    {
        parent::__construct($rule);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->rule->getId(),
            'tenant_id'        => $this->rule->getTenantId(),
            'product_id'       => $this->rule->getProductId(),
            'variant_id'       => $this->rule->getVariantId(),
            'warehouse_id'     => $this->rule->getWarehouseId(),
            'minimum_quantity' => $this->rule->getMinimumQuantity(),
            'maximum_quantity' => $this->rule->getMaximumQuantity(),
            'reorder_quantity' => $this->rule->getReorderQuantity(),
            'is_active'        => $this->rule->isActive(),
        ];
    }
}
