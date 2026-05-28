<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockAdjustmentLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\CreateStockAdjustmentLineServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateStockAdjustmentLineService implements CreateStockAdjustmentLineServiceInterface
{
    public function __construct(private readonly StockAdjustmentLineServiceInterface $stockAdjustmentLineService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->stockAdjustmentLineService->createLine($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
