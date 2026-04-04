<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryBatch;

interface CreateInventoryBatchServiceInterface
{
    public function execute(array $data): InventoryBatch;
}
