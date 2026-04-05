<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Contracts;

interface ConsumeValuationLayersServiceInterface
{
    /**
     * Consume valuation layers for an outbound stock movement.
     * Returns the weighted-average unit cost consumed.
     *
     * @param string $method  fifo | lifo | average
     */
    public function execute(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        string $method,
    ): float;
}
