<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryCycleCount;

interface CreateCycleCountServiceInterface
{
    public function execute(array $data): InventoryCycleCount;
}
