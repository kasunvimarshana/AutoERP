<?php
namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Domain\Entities\Warehouse;

interface DeleteWarehouseServiceInterface
{
    public function execute(Warehouse $warehouse): bool;
}
