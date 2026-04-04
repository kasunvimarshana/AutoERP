<?php
namespace Modules\PurchaseOrder\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;
class PurchaseOrder extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly int $supplierId,
        public readonly string $poNumber,
        public readonly string $status,
        public readonly ?float $totalAmount = null,
        public readonly ?float $taxAmount = null,
        public readonly ?string $currency = 'USD',
        public readonly ?string $notes = null,
        public readonly ?\DateTimeImmutable $expectedDeliveryDate = null,
        public readonly ?\DateTimeImmutable $approvedAt = null,
        public readonly ?int $approvedBy = null,
    ) {
        parent::__construct($id);
    }
}
