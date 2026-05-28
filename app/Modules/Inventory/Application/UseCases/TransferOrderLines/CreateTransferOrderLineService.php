<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\TransferOrderLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\TransferOrderLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TransferOrderLines\CreateTransferOrderLineServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateTransferOrderLineService implements CreateTransferOrderLineServiceInterface
{
    public function __construct(private readonly TransferOrderLineServiceInterface $transferOrderLineService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->transferOrderLineService->createLine($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
