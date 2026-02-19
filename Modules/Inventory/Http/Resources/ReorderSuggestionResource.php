<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReorderSuggestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->resource['product_id'],
            'product_name' => $this->resource['product_name'],
            'product_sku' => $this->resource['product_sku'],
            'warehouse_id' => $this->resource['warehouse_id'],
            'warehouse_name' => $this->resource['warehouse_name'],
            'current_quantity' => $this->resource['current_quantity'],
            'available_quantity' => $this->resource['available_quantity'],
            'reserved_quantity' => $this->resource['reserved_quantity'],
            'reorder_point' => $this->resource['reorder_point'],
            'reorder_quantity' => $this->resource['reorder_quantity'],
            'suggested_order_quantity' => $this->resource['suggested_order_quantity'],
            'days_to_stockout' => $this->resource['days_to_stockout'],
            'priority' => $this->resource['priority'],
            'priority_label' => $this->getPriorityLabel($this->resource['priority']),
            'estimated_cost' => $this->resource['estimated_cost'],
        ];
    }

    /**
     * Get priority label.
     */
    private function getPriorityLabel(int $priority): string
    {
        return match (true) {
            $priority >= 80 => 'Critical',
            $priority >= 60 => 'High',
            $priority >= 40 => 'Medium',
            default => 'Low',
        };
    }
}
