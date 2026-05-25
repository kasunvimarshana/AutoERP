<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations;

use Modules\Core\Application\Results\Result;

interface UpdateWarehouseLocationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}