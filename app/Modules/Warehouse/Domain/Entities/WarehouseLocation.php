<?php
namespace Modules\Warehouse\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class WarehouseLocation extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $warehouseId,
        public readonly int $zoneId,
        public readonly string $code,
        public readonly string $barcode,
        public readonly string $locationType,
        public readonly bool $isActive = true,
        public readonly ?string $aisle = null,
        public readonly ?string $bay = null,
        public readonly ?string $level = null,
        public readonly ?string $bin = null,
        public readonly ?float $maxWeight = null,
        public readonly ?float $maxVolume = null,
    ) {
        parent::__construct($id);
    }
}
