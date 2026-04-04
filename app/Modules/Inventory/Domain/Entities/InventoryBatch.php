<?php
namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class InventoryBatch extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly string $batchNumber,
        public readonly ?\DateTimeImmutable $manufacturingDate = null,
        public readonly ?\DateTimeImmutable $expiryDate = null,
        public readonly ?string $supplierId = null,
        public readonly string $status = 'active',
        public readonly ?array $attributes = null,
    ) {
        parent::__construct($id);
    }
}
