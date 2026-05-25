<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts\UseCases\Warehouses;

use Modules\Core\Application\Results\Result;

interface ListWarehousesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}