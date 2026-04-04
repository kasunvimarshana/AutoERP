<?php
namespace Modules\StockMovement\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;
class TransferStockData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $fromWarehouseId,
        public readonly int $fromLocationId,
        public readonly int $toWarehouseId,
        public readonly int $toLocationId,
        public readonly float $quantity,
        public readonly string $reference,
        public readonly ?int $batchId = null,
        public readonly ?int $variantId = null,
    ) {}
}
