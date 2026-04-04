<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Domain\Entities\WarehouseZone;

interface CreateWarehouseZoneServiceInterface
{
    public function execute(WarehouseZoneData $data): WarehouseZone;
}
