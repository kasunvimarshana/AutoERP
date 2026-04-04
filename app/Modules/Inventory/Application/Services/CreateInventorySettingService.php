<?php
namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\CreateInventorySettingServiceInterface;
use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Domain\Entities\InventorySetting;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;

class CreateInventorySettingService implements CreateInventorySettingServiceInterface
{
    public function __construct(private readonly InventorySettingRepositoryInterface $repository) {}

    public function execute(InventorySettingData $data): InventorySetting
    {
        return $this->repository->create($data->toArray());
    }
}
