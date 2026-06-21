<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesAllocationResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'allocation_number' => $this->allocation_number,
            'allocation_date' => $this->allocation_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'sales_order' => $this->whenLoaded('salesOrder', fn () => $this->summary($this->salesOrder, ['sales_order_number', 'status'])),
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'notes' => $this->notes,
            'released_at' => $this->released_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => SalesAllocationLineResource::collection($this->lines)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
