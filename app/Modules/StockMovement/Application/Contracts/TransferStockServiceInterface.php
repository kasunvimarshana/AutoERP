<?php
declare(strict_types=1);
namespace Modules\StockMovement\Application\Contracts;

use Modules\StockMovement\Domain\Entities\StockMovement;

interface TransferStockServiceInterface
{
    /**
     * Transfer stock from one warehouse to another.
     * Creates an ISSUE movement at the source and a RECEIPT movement at the destination.
     *
     * @return StockMovement[]  [issueMovement, receiptMovement]
     */
    public function execute(
        int $tenantId,
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        float $unitCost,
        string $reference,
        int $createdBy,
        ?int $fromLocationId = null,
        ?int $toLocationId = null,
        ?string $notes = null,
    ): array;
}
