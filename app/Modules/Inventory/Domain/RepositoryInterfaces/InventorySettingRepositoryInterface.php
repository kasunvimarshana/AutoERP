<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventorySetting;

interface InventorySettingRepositoryInterface extends RepositoryInterface
{
    public function save(InventorySetting $setting): InventorySetting;

    public function findByTenant(int $tenantId): ?InventorySetting;
}
