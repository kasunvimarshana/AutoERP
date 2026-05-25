<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations;

use Modules\Core\Application\Results\Result;

interface DeleteWarehouseLocationServiceInterface
{
    public function execute(int|string $id): Result;
}