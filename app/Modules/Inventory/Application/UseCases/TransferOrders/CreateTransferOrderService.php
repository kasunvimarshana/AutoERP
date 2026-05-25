<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\TransferOrders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\CreateTransferOrderServiceInterface;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateTransferOrderService implements CreateTransferOrderServiceInterface
{
    public function __construct(private readonly TransferOrderRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}