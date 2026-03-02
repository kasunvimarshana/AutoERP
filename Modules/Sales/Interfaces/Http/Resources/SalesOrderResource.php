<?php

declare(strict_types=1);

namespace Modules\Sales\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Domain\Entities\SalesOrder;

class SalesOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var SalesOrder $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'tenant_id' => $order->tenantId,
            'order_number' => $order->orderNumber,
            'customer_name' => $order->customerName,
            'customer_email' => $order->customerEmail,
            'customer_phone' => $order->customerPhone,
            'status' => $order->status,
            'order_date' => $order->orderDate,
            'due_date' => $order->dueDate,
            'notes' => $order->notes,
            'currency' => $order->currency,
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->taxAmount,
            'discount_amount' => $order->discountAmount,
            'total_amount' => $order->totalAmount,
            'lines' => array_map(
                fn ($line) => (new SalesOrderLineResource($line))->resolve(),
                $order->lines
            ),
            'created_at' => $order->createdAt,
        ];
    }
}
