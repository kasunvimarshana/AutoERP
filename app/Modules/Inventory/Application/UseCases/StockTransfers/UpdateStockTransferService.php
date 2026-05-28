<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockTransfers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\UpdateStockTransferServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateStockTransferService implements UpdateStockTransferServiceInterface
{
    public function __construct(private readonly StockTransferServiceInterface $stockTransferService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->stockTransferService->updateTransfer($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
