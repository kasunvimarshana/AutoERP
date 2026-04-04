<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Domain\Entities\InventorySetting;

interface UpdateInventorySettingServiceInterface
{
    public function execute(InventorySetting $setting, InventorySettingData $data): InventorySetting;
}
