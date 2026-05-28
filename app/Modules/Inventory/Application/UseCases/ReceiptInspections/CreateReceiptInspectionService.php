<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\ReceiptInspections;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\ReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\CreateReceiptInspectionServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateReceiptInspectionService implements CreateReceiptInspectionServiceInterface
{
    public function __construct(private readonly ReceiptInspectionServiceInterface $receiptInspectionService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->receiptInspectionService->createInspection($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
