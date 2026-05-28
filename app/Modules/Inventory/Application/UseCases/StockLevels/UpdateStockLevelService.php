<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockLevels;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\UpdateStockLevelServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateStockLevelService implements UpdateStockLevelServiceInterface
{
    public function __construct(private readonly StockLevelServiceInterface $stockLevelService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->stockLevelService->updateLevel($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
