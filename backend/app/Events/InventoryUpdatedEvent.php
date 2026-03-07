<?php

namespace App\Events;

use App\Models\Inventory;

class InventoryUpdatedEvent extends BaseEvent
{
    public function __construct(
        public readonly Inventory $inventory,
        public readonly string    $action,
        public readonly int       $quantityChanged
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'inventory.updated';
    }

    protected function toData(): array
    {
        return [
            'inventory_id'     => $this->inventory->id,
            'product_id'       => $this->inventory->product_id,
            'action'           => $this->action,
            'quantity_changed' => $this->quantityChanged,
            'current_quantity' => $this->inventory->quantity,
            'available'        => $this->inventory->available_quantity,
            'reserved'         => $this->inventory->reserved_quantity,
        ];
    }
}
