<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Domain\Inventory\Models\StockMovement;

/**
 * Stock Movement Repository Interface
 */
interface StockMovementRepositoryInterface extends BaseRepositoryInterface
{
    public function recordMovement(array $data): StockMovement;
    public function getMovementsForProduct(string $productId, array $params = []): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
