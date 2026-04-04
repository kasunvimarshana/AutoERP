<?php
namespace Modules\GoodsReceipt\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;
class GoodsReceiptData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly string $grNumber,
        public readonly array $lines = [],
        public readonly ?int $purchaseOrderId = null,
        public readonly ?int $supplierId = null,
        public readonly ?string $supplierReference = null,
        public readonly ?string $notes = null,
        public readonly ?int $receivedBy = null,
    ) {}
}
