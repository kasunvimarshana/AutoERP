<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int    $id
 * @property int    $order_id
 * @property int    $product_id
 * @property string $product_name
 * @property string|null $product_sku
 * @property int    $quantity
 * @property float  $unit_price
 * @property float  $total_price
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'order_id'     => $this->order_id,
            'product_id'   => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku'  => $this->product_sku,
            'quantity'     => (int) $this->quantity,
            'unit_price'   => (float) $this->unit_price,
            'total_price'  => (float) $this->total_price,
            'status'       => $this->status,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'service' => 'order-service',
                'version' => '1.0.0',
            ],
        ];
    }
}
