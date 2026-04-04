<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Domain\Entities\InventorySetting;

interface CreateInventorySettingServiceInterface
{
    public function execute(InventorySettingData $data): InventorySetting;
}
