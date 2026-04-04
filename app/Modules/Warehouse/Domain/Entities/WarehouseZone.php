<?php
namespace Modules\Warehouse\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class WarehouseZone extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $warehouseId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $description = null,
    ) {
        parent::__construct($id);
    }
}
