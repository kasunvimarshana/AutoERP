<?php
namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\InventorySetting;

interface InventorySettingRepositoryInterface
{
    public function findByTenant(int $tenantId): ?InventorySetting;
    public function save(InventorySetting $setting): InventorySetting;
    public function create(array $data): InventorySetting;
    public function update(InventorySetting $setting, array $data): InventorySetting;
}
