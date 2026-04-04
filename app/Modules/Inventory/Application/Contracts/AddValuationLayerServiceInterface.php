<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\AddValuationLayerData;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

interface AddValuationLayerServiceInterface
{
    public function execute(AddValuationLayerData $data): InventoryValuationLayer;
}
