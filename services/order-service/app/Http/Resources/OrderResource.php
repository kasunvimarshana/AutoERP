<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int         $id
 * @property string      $order_number
 * @property string      $customer_id
 * @property string      $customer_name
 * @property string      $customer_email
 * @property string      $status
 * @property float       $total_amount
 * @property float       $tax_amount
 * @property float       $discount_amount
 * @property array|null  $shipping_address
 * @property array|null  $billing_address
 * @property string|null $notes
 * @property string      $saga_status
 * @property array|null  $saga_compensation_data
 * @property bool        $is_cancellable
 * @property bool        $is_shippable
 * @property bool        $is_deliverable
 * @property \Carbon\Carbon|null $placed_at
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon|null $shipped_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_number'     => $this->order_number,
            'customer_id'      => $this->customer_id,
            'customer_name'    => $this->customer_name,
            'customer_email'   => $this->customer_email,
            'status'           => $this->status,
            'total_amount'     => (float) $this->total_amount,
            'tax_amount'       => (float) $this->tax_amount,
            'discount_amount'  => (float) $this->discount_amount,
            'shipping_address' => $this->shipping_address,
            'billing_address'  => $this->billing_address,
            'notes'            => $this->notes,
            'saga_status'      => $this->saga_status,
            'is_cancellable'   => $this->is_cancellable,
            'is_shippable'     => $this->is_shippable,
            'is_deliverable'   => $this->is_deliverable,
            'items'            => OrderItemResource::collection($this->whenLoaded('items')),
            'placed_at'        => $this->placed_at?->toIso8601String(),
            'confirmed_at'     => $this->confirmed_at?->toIso8601String(),
            'shipped_at'       => $this->shipped_at?->toIso8601String(),
            'delivered_at'     => $this->delivered_at?->toIso8601String(),
            'cancelled_at'     => $this->cancelled_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
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
