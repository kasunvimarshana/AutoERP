<?php
namespace Modules\GoodsReceipt\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class GoodsReceiptLine extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $goodsReceiptId,
        public readonly int $productId,
        public readonly int $locationId,
        public readonly float $expectedQty,
        public readonly float $receivedQty,
        public readonly ?int $variantId = null,
        public readonly ?int $purchaseOrderLineId = null,
        public readonly ?int $batchId = null,
        public readonly ?string $lotNumber = null,
        public readonly ?string $serialNumber = null,
        public readonly ?float $unitCost = null,
        public readonly string $condition = 'good',
        public readonly ?string $notes = null,
    ) {
        parent::__construct($id);
    }
}
