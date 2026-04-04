<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\AdjustInventoryData;
use Modules\Inventory\Domain\Entities\InventoryLevel;

interface AdjustInventoryServiceInterface
{
    public function execute(AdjustInventoryData $data): InventoryLevel;
}
