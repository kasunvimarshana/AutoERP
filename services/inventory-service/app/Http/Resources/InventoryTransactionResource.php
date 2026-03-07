<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int         $id
 * @property int         $inventory_id
 * @property int         $product_id
 * @property string      $type
 * @property int         $quantity
 * @property int         $previous_quantity
 * @property int         $new_quantity
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $notes
 * @property string|null $performed_by
 * @property \Carbon\Carbon $created_at
 */
class InventoryTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'inventory_id'      => $this->inventory_id,
            'product_id'        => $this->product_id,
            'type'              => $this->type,
            'quantity'          => (int) $this->quantity,
            'previous_quantity' => (int) $this->previous_quantity,
            'new_quantity'      => (int) $this->new_quantity,
            'quantity_delta'    => (int) $this->quantity,
            'reference_type'    => $this->reference_type,
            'reference_id'      => $this->reference_id,
            'notes'             => $this->notes,
            'performed_by'      => $this->performed_by,
            'created_at'        => $this->created_at?->toIso8601String(),
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
