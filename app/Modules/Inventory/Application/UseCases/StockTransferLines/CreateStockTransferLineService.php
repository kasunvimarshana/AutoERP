<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockTransferLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\CreateStockTransferLineServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateStockTransferLineService implements CreateStockTransferLineServiceInterface
{
    public function __construct(private readonly StockTransferLineServiceInterface $stockTransferLineService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->stockTransferLineService->createLine($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
