<?php
namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\UpdateInventorySettingServiceInterface;
use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Domain\Entities\InventorySetting;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;

class UpdateInventorySettingService implements UpdateInventorySettingServiceInterface
{
    public function __construct(private readonly InventorySettingRepositoryInterface $repository) {}

    public function execute(InventorySetting $setting, InventorySettingData $data): InventorySetting
    {
        return $this->repository->update($setting, $data->toArray());
    }
}
