<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts\UseCases\Warehouses;

use Modules\Core\Application\Results\Result;

interface GetWarehouseServiceInterface
{
    public function execute(int|string $id): Result;
}