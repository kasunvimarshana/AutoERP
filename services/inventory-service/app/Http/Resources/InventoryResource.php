<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int         $id
 * @property int         $product_id
 * @property int         $quantity
 * @property int         $reserved_quantity
 * @property int         $available_quantity
 * @property string|null $warehouse_location
 * @property int         $reorder_level
 * @property int         $reorder_quantity
 * @property float|null  $unit_cost
 * @property string      $status
 * @property bool        $is_low_stock
 * @property bool        $is_out_of_stock
 * @property \Carbon\Carbon|null $last_counted_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'product_id'         => $this->product_id,
            'quantity'           => (int) $this->quantity,
            'reserved_quantity'  => (int) $this->reserved_quantity,
            'available_quantity' => (int) $this->available_quantity,
            'warehouse_location' => $this->warehouse_location,
            'reorder_level'      => (int) $this->reorder_level,
            'reorder_quantity'   => (int) $this->reorder_quantity,
            'unit_cost'          => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'status'             => $this->status,
            'is_active'          => $this->status === 'active',
            'is_low_stock'       => $this->is_low_stock,
            'is_out_of_stock'    => $this->is_out_of_stock,
            'last_counted_at'    => $this->last_counted_at?->toIso8601String(),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'service' => 'inventory-service',
                'version' => '1.0.0',
            ],
        ];
    }
}
