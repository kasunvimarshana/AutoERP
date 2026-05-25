<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts\UseCases\Warehouses;

use Modules\Core\Application\Results\Result;

interface CreateWarehouseServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}