<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Domain\Entities\WarehouseZone;

interface DeleteWarehouseZoneServiceInterface
{
    public function execute(WarehouseZone $zone): bool;
}
