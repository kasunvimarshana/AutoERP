<?php
namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class InventorySerial extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly string $serialNumber,
        public readonly string $status = 'available',
        public readonly ?int $currentWarehouseId = null,
        public readonly ?int $currentLocationId = null,
        public readonly ?int $batchId = null,
        public readonly ?\DateTimeImmutable $warrantyExpiresAt = null,
    ) {
        parent::__construct($id);
    }
}
