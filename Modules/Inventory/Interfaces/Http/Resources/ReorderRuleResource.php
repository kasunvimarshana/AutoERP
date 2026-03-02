<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\ReorderRule;

class ReorderRuleResource extends JsonResource
{
    /** @var ReorderRule */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'product_id' => $this->resource->productId,
            'warehouse_id' => $this->resource->warehouseId,
            'reorder_point' => $this->resource->reorderPoint,
            'reorder_quantity' => $this->resource->reorderQuantity,
            'is_active' => $this->resource->isActive,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
