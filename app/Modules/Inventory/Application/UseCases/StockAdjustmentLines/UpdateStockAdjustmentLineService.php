<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockAdjustmentLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\UpdateStockAdjustmentLineServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateStockAdjustmentLineService implements UpdateStockAdjustmentLineServiceInterface
{
    public function __construct(private readonly StockAdjustmentLineServiceInterface $stockAdjustmentLineService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->stockAdjustmentLineService->updateLine($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
