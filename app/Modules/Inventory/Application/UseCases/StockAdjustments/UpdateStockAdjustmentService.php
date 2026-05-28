<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockAdjustments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockAdjustmentServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustments\UpdateStockAdjustmentServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateStockAdjustmentService implements UpdateStockAdjustmentServiceInterface
{
    public function __construct(private readonly StockAdjustmentServiceInterface $stockAdjustmentService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->stockAdjustmentService->updateAdjustment($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
