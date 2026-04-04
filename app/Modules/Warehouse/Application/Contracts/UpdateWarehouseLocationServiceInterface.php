<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Application\DTOs\WarehouseLocationData;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;

interface UpdateWarehouseLocationServiceInterface
{
    public function execute(WarehouseLocation $location, WarehouseLocationData $data): WarehouseLocation;
}
