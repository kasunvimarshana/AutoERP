<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\TransferOrders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\GetTransferOrderServiceInterface;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class GetTransferOrderService implements GetTransferOrderServiceInterface
{
    public function __construct(private readonly TransferOrderRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'TransferOrder not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}