<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Core\Events\BaseEvent;

/**
 * Stock Movement Event
 * 
 * Fired when inventory stock is moved (in, out, adjustment, transfer)
 */
class StockMovement extends BaseEvent
{
    public readonly int $productId;
    public readonly string $movementType;
    public readonly float $quantity;
    public readonly float $balanceAfter;
    public readonly ?int $branchId;
    public readonly ?int $locationId;

    /**
     * Create a new event instance
     *
     * @param int $tenantId
     * @param int $productId
     * @param string $movementType
     * @param float $quantity
     * @param float $balanceAfter
     * @param int|null $branchId
     * @param int|null $locationId
     * @param int|null $userId
     * @param array|null $metadata
     */
    public function __construct(
        int $tenantId,
        int $productId,
        string $movementType,
        float $quantity,
        float $balanceAfter,
        ?int $branchId = null,
        ?int $locationId = null,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
        $this->productId = $productId;
        $this->movementType = $movementType;
        $this->quantity = $quantity;
        $this->balanceAfter = $balanceAfter;
        $this->branchId = $branchId;
        $this->locationId = $locationId;
    }

    /**
     * Get event payload
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'product_id' => $this->productId,
            'movement_type' => $this->movementType,
            'quantity' => $this->quantity,
            'balance_after' => $this->balanceAfter,
            'branch_id' => $this->branchId,
            'location_id' => $this->locationId,
        ]);
    }
}
