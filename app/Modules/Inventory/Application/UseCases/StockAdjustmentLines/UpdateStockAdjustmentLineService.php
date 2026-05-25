<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockAdjustmentLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\UpdateStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentLineRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateStockAdjustmentLineService implements UpdateStockAdjustmentLineServiceInterface
{
    public function __construct(private readonly StockAdjustmentLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockAdjustmentLine not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}