<?php
namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ReceiveStockData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly int $locationId,
        public readonly float $quantity,
        public readonly float $unitCost,
        public readonly string $valuationMethod,
        public readonly ?int $batchId = null,
        public readonly ?string $receiptDate = null,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
    ) {}
}
