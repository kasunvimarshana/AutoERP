<?php
namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class AdjustInventoryData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly int $locationId,
        public readonly float $newQuantity,
        public readonly string $reason,
        public readonly ?int $batchId = null,
    ) {}
}
