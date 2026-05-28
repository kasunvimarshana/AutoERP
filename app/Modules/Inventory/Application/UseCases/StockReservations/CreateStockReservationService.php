<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockReservations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\CreateStockReservationServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateStockReservationService implements CreateStockReservationServiceInterface
{
    public function __construct(private readonly StockReservationServiceInterface $stockReservationService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->stockReservationService->reserve($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
