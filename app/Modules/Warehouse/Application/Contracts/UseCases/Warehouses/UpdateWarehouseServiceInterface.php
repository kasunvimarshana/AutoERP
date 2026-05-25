<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts\UseCases\Warehouses;

use Modules\Core\Application\Results\Result;

interface UpdateWarehouseServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}