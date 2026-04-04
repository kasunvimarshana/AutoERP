<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;

interface CreateWarehouseServiceInterface
{
    public function execute(WarehouseData $data): Warehouse;
}
