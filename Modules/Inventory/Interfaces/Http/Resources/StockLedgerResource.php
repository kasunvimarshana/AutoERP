<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;

class StockLedgerResource extends JsonResource
{
    /** @var StockLedgerEntry */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'warehouse_id' => $this->resource->warehouseId,
            'product_id' => $this->resource->productId,
            'transaction_type' => $this->resource->transactionType,
            'quantity' => $this->resource->quantity,
            'unit_cost' => $this->resource->unitCost,
            'total_cost' => $this->resource->totalCost,
            'reference_type' => $this->resource->referenceType,
            'reference_id' => $this->resource->referenceId,
            'notes' => $this->resource->notes,
            'created_at' => $this->resource->createdAt,
        ];
    }
}
