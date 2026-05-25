<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\StockReservations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\UpdateStockReservationServiceInterface;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateStockReservationService implements UpdateStockReservationServiceInterface
{
    public function __construct(private readonly StockReservationRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockReservation not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}