<?php

declare(strict_types=1);

namespace Modules\StockMovement\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\StockMovement\Domain\Entities\StockMovement;

interface StockMovementRepositoryInterface extends RepositoryInterface
{
    public function save(StockMovement $movement): StockMovement;
    public function findByReferenceNumber(int $tenantId, string $ref): ?StockMovement;
    public function findByProduct(int $tenantId, int $productId): Collection;
    public function findByMovementType(int $tenantId, string $type): Collection;
}
