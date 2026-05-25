<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\TransferOrderLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\DeleteTransferOrderLineServiceInterface;
use Modules\Inventory\Application\Repositories\TransferOrderLineRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class DeleteTransferOrderLineService implements DeleteTransferOrderLineServiceInterface
{
    public function __construct(private readonly TransferOrderLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'TransferOrderLine not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}