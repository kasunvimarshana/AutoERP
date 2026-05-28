<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\TransferOrders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\TransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrders\CreateTransferOrderServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateTransferOrderService implements CreateTransferOrderServiceInterface
{
    public function __construct(private readonly TransferOrderServiceInterface $transferOrderService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->transferOrderService->createOrder($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
