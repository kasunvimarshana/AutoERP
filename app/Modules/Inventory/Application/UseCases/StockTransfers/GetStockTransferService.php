<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockTransfers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\GetStockTransferServiceInterface;
use Modules\Inventory\Application\Repositories\StockTransferRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class GetStockTransferService implements GetStockTransferServiceInterface
{
    public function __construct(private readonly StockTransferRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockTransfer not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}