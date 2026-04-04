<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;

interface UpdateWarehouseServiceInterface
{
    public function execute(Warehouse $warehouse, WarehouseData $data): Warehouse;
}
