<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockMovements;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\UpdateStockMovementServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateStockMovementService implements UpdateStockMovementServiceInterface
{
    public function __construct(private readonly StockMovementServiceInterface $stockMovementService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->stockMovementService->updateMovement($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
