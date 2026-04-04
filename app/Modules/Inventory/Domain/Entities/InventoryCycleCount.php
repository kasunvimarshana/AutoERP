<?php
namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class InventoryCycleCount extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly string $method,
        public readonly string $status = 'draft',
        public readonly ?string $reference = null,
        public readonly ?int $assignedTo = null,
        public readonly ?\DateTimeImmutable $scheduledAt = null,
        public readonly ?\DateTimeImmutable $completedAt = null,
    ) {
        parent::__construct($id);
    }
}
