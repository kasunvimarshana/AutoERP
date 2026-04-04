<?php
namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class WarehouseLocationData extends BaseDTO
{
    public function __construct(
        public readonly int $warehouseId,
        public readonly int $zoneId,
        public readonly string $code,
        public readonly string $barcode,
        public readonly string $locationType = 'shelf',
        public readonly bool $isActive = true,
        public readonly ?string $aisle = null,
        public readonly ?string $bay = null,
        public readonly ?string $level = null,
        public readonly ?string $bin = null,
        public readonly ?float $maxWeight = null,
        public readonly ?float $maxVolume = null,
    ) {}
}
