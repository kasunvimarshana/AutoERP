<?php

namespace Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'supplier_information' => $this->when(
                $this->relationLoaded('supplier'),
                fn() => [
                    'supplier_id' => $this->supplier_id,
                    'supplier_name' => $this->supplier?->name,
                    'supplier_code' => $this->supplier?->code,
                    'supplier_email' => $this->supplier?->email,
                ]
            ),
            'order_status' => [
                'current_status' => $this->status?->value,
                'status_label' => $this->getStatusLabel(),
            ],
            'scheduling' => [
                'order_placed_date' => $this->order_date->toDateString(),
                'order_timestamp' => $this->order_date->toIso8601String(),
                'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
                'expected_delivery_timestamp' => $this->expected_delivery_date?->toIso8601String(),
                'delivery_offset_days' => $this->expected_delivery_date ? now()->diffInDays($this->expected_delivery_date, false) : null,
            ],
            'delivery_location' => $this->delivery_address,
            'financial_breakdown' => [
                'subtotal_amount' => $this->formatMoney($this->subtotal),
                'tax_amount' => $this->formatMoney($this->tax_amount),
                'discount_amount' => $this->formatMoney($this->discount_amount),
                'total_amount' => $this->formatMoney($this->total_amount),
                'discount_rate' => $this->calculateDiscountRate(),
            ],
            'order_notes' => $this->notes,
            'order_line_items' => $this->when(
                $this->relationLoaded('items'),
                fn() => [
                    'total_line_items' => $this->items->count(),
                    'items' => PurchaseOrderItemResource::collection($this->items),
                ]
            ),
            'goods_receipts' => $this->when(
                $this->relationLoaded('goodsReceipts'),
                fn() => [
                    'receipt_count' => $this->goodsReceipts->count(),
                    'total_received' => $this->goodsReceipts->sum('quantity'),
                ]
            ),
            'audit_timestamps' => [
                'created' => $this->created_at?->toIso8601String(),
                'last_modified' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status?->value) {
            'draft' => 'Draft Purchase Order',
            'submitted' => 'Submitted to Supplier',
            'approved' => 'Approved',
            'received' => 'Goods Received',
            'closed' => 'Order Closed',
            'cancelled' => 'Order Cancelled',
            default => 'Unknown Status',
        };
    }

    private function calculateDiscountRate(): float
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
