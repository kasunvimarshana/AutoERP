<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use App\Core\Events\BaseDomainEvent;
use Modules\Inventory\Models\InventoryTransaction;

/**
 * Inventory Adjusted Event
 *
 * Dispatched when inventory levels are adjusted.
 * Triggers:
 * - Update stock alerts
 * - Notify procurement team
 * - Update analytics
 * - Check reorder points
 */
class InventoryAdjusted extends BaseDomainEvent
{
    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly InventoryTransaction $transaction,
        public readonly string $reason
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getEventPayload(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'item_id' => $this->transaction->inventory_item_id,
            'quantity_change' => $this->transaction->quantity,
            'transaction_type' => $this->transaction->transaction_type,
            'reason' => $this->reason,
            'new_balance' => $this->transaction->balance_after,
        ];
    }
}
