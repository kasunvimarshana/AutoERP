<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Inventory\Domain\Entities\InventorySetting;

interface FindInventorySettingServiceInterface extends ReadServiceInterface
{
    public function findByTenant(int $tenantId): ?InventorySetting;
}
