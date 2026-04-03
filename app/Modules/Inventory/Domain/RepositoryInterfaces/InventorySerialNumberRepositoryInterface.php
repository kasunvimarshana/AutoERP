<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventorySerialNumber;

interface InventorySerialNumberRepositoryInterface extends RepositoryInterface
{
    public function save(InventorySerialNumber $serialNumber): InventorySerialNumber;

    public function findBySerial(int $tenantId, int $productId, string $serial): ?InventorySerialNumber;

    public function findByLocation(int $tenantId, int $locationId): Collection;
}
