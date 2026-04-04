<?php
namespace Modules\PurchaseOrder\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;
class PurchaseOrderData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly int $supplierId,
        public readonly string $poNumber,
        public readonly array $lines = [],
        public readonly ?string $currency = 'USD',
        public readonly ?string $notes = null,
        public readonly ?string $expectedDeliveryDate = null,
    ) {}
}
