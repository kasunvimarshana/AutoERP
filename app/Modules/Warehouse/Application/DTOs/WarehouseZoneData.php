<?php
namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class WarehouseZoneData extends BaseDTO
{
    public function __construct(
        public readonly int $warehouseId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type = 'storage',
        public readonly string $status = 'active',
        public readonly ?string $description = null,
    ) {}
}
