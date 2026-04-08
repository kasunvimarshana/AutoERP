<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\StockMovement;

interface StockMovementServiceInterface
{
    public function getMovement(string $tenantId, string $id): StockMovement;

    /** @return StockMovement[] */
    public function getMovementsByProduct(string $tenantId, string $productId): array;

    public function recordMovement(string $tenantId, array $data): StockMovement;
}
