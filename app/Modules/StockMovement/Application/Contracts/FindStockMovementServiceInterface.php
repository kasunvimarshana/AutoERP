<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\StockMovement\Domain\Entities\StockMovement;

interface FindStockMovementServiceInterface extends ReadServiceInterface
{
    public function findByProduct(int $tenantId, int $productId): Collection;
    public function findByMovementType(int $tenantId, string $type): Collection;
}
