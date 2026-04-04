<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Domain\Entities\WarehouseLocation;

interface DeleteWarehouseLocationServiceInterface
{
    public function execute(WarehouseLocation $location): bool;
}
