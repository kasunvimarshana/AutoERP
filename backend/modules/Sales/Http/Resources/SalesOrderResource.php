<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_details' => $this->when(
                $this->relationLoaded('customer'),
                fn() => [
                    'customer_id' => $this->customer_id,
                    'customer_name' => $this->customer?->customer_name,
                    'customer_code' => $this->customer?->customer_code,
                ]
            ),
            'order_status' => [
                'status' => $this->status?->value,
                'status_label' => $this->getStatusLabel(),
            ],
            'important_dates' => [
                'order_placed' => $this->order_date->toDateString(),
                'order_timestamp' => $this->order_date->toIso8601String(),
                'expected_delivery' => $this->delivery_date?->toDateString(),
                'delivery_timestamp' => $this->delivery_date?->toIso8601String(),
                'delivery_offset_days' => $this->delivery_date ? now()->diffInDays($this->delivery_date, false) : null,
            ],
            'delivery_information' => [
                'billing_address' => $this->billing_address,
                'shipping_address' => $this->shipping_address,
            ],
            'order_financial_summary' => [
                'subtotal' => $this->formatMoney($this->subtotal),
                'tax' => $this->formatMoney($this->tax_amount),
                'discount' => $this->formatMoney($this->discount_amount),
                'total' => $this->formatMoney($this->total_amount),
                'discount_percentage' => $this->calculateDiscountPercentage(),
            ],
            'order_notes' => $this->notes,
            'order_items' => $this->when(
                $this->relationLoaded('items'),
                fn() => [
                    'item_count' => $this->items->count(),
                    'items' => SalesOrderItemResource::collection($this->items),
                ]
            ),
            'invoices' => $this->when(
                $this->relationLoaded('invoices'),
                fn() => [
                    'invoice_count' => $this->invoices->count(),
                    'total_invoiced' => $this->formatMoney($this->invoices->sum('total_amount')),
                ]
            ),
            'timestamps' => [
                'created' => $this->created_at?->toIso8601String(),
                'last_modified' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status?->value) {
            'draft' => 'Draft Order',
            'confirmed' => 'Order Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => 'Unknown Status',
        };
    }

    private function calculateDiscountPercentage(): float
    {
        if ($this->subtotal <= 0) {
            return 0;
        }

        return round(($this->discount_amount / $this->subtotal) * 100, 2);
    }

    private function formatMoney($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}
