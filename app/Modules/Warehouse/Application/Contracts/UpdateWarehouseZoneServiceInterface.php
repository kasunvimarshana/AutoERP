<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Domain\Entities\WarehouseZone;

interface UpdateWarehouseZoneServiceInterface
{
    public function execute(WarehouseZone $zone, WarehouseZoneData $data): WarehouseZone;
}
