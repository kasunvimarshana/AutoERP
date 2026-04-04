<?php
namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class IssueStockData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly float $quantity,
        public readonly string $valuationMethod,
        public readonly string $allocationAlgorithm,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
    ) {}
}
