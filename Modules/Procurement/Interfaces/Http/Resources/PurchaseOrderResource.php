<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Procurement\Domain\Entities\PurchaseOrder;

class PurchaseOrderResource extends JsonResource
{
    public function __construct(private readonly PurchaseOrder $order)
    {
        parent::__construct($order);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->order->id,
            'tenant_id' => $this->order->tenantId,
            'supplier_id' => $this->order->supplierId,
            'order_number' => $this->order->orderNumber,
            'status' => $this->order->status,
            'order_date' => $this->order->orderDate,
            'expected_delivery_date' => $this->order->expectedDeliveryDate,
            'notes' => $this->order->notes,
            'currency' => $this->order->currency,
            'subtotal' => $this->order->subtotal,
            'tax_amount' => $this->order->taxAmount,
            'discount_amount' => $this->order->discountAmount,
            'total_amount' => $this->order->totalAmount,
            'lines' => array_map(
                fn ($line) => (new PurchaseOrderLineResource($line))->resolve(),
                $this->order->lines
            ),
            'created_at' => $this->order->createdAt,
            'updated_at' => $this->order->updatedAt,
        ];
    }
}
