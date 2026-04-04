<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Application\DTOs\WarehouseLocationData;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;

interface CreateWarehouseLocationServiceInterface
{
    public function execute(WarehouseLocationData $data): WarehouseLocation;
}
