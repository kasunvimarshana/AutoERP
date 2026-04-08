<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface WarehouseServiceInterface extends ServiceInterface
{
    public function createWarehouse(array $data): mixed;
    public function updateWarehouse(string $id, array $data): mixed;
}
