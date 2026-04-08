<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Inventory\Domain\Entities\InventorySerialNumber;

interface FindInventorySerialNumberServiceInterface extends ReadServiceInterface
{
    public function findBySerial(int $tenantId, int $productId, string $serial): ?InventorySerialNumber;
    public function findByLocation(int $tenantId, int $locationId): Collection;
}
