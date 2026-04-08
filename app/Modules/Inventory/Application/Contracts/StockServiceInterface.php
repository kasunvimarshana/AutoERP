<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\StockAdjustmentData;
use Modules\Inventory\Application\DTOs\StockTransferData;

interface StockServiceInterface
{
    public function adjust(StockAdjustmentData $dto, int $tenantId): mixed;

    public function transfer(StockTransferData $dto, int $tenantId): mixed;

    public function getStockByProduct(int $productId, int $tenantId): Collection;

    public function getStockByLocation(int $locationId): Collection;

    public function listMovements(array $filters = [], ?int $perPage = null): mixed;
}
