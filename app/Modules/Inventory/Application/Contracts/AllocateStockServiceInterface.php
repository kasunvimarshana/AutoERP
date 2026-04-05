<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Contracts;

interface AllocateStockServiceInterface
{
    /**
     * Allocate (reserve) stock for an outbound order line.
     * Uses the configured rotation strategy to select the optimal batch/layer.
     *
     * @param string $strategy  fifo | lifo | fefo (first-expired-first-out)
     * @return array  Allocated batch references: [['batch_id'=>int, 'quantity'=>float, 'expires_at'=>?string], ...]
     */
    public function execute(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        string $strategy = 'fifo',
    ): array;
}
