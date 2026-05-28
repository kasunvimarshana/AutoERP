<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockLevels;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\CreateStockLevelServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateStockLevelService implements CreateStockLevelServiceInterface
{
    public function __construct(private readonly StockLevelServiceInterface $stockLevelService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->stockLevelService->createLevel($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
