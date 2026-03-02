<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Procurement\Domain\Entities\PurchaseOrderLine;

class PurchaseOrderLineResource extends JsonResource
{
    public function __construct(private readonly PurchaseOrderLine $line)
    {
        parent::__construct($line);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->line->id,
            'purchase_order_id' => $this->line->purchaseOrderId,
            'product_id' => $this->line->productId,
            'description' => $this->line->description,
            'quantity_ordered' => $this->line->quantityOrdered,
            'quantity_received' => $this->line->quantityReceived,
            'unit_cost' => $this->line->unitCost,
            'tax_rate' => $this->line->taxRate,
            'discount_rate' => $this->line->discountRate,
            'line_total' => $this->line->lineTotal,
            'created_at' => $this->line->createdAt,
            'updated_at' => $this->line->updatedAt,
        ];
    }
}
