<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface InventoryManagerServiceInterface
{
    /**
     * Allocate stock for a product using the specified picking strategy.
     *
     * Supported strategies: 'fefo', 'fifo', 'lifo'
     *
     * Returns an array of allocation line items:
     * [['batch_id' => ?int, 'quantity' => float, 'expires_at' => ?string], ...]
     */
    public function allocateStock(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        string $strategy = 'fefo',
    ): array;

    /**
     * Receive stock into inventory (inbound flow).
     */
    public function receiveStock(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        float $unitCost,
        ?int $locationId = null,
        ?string $batchNumber = null,
        ?\DateTimeInterface $expiresAt = null,
    ): void;

    /**
     * Issue (consume) stock from inventory (outbound flow).
     */
    public function issueStock(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        ?int $locationId = null,
        string $strategy = 'fefo',
    ): void;
}
