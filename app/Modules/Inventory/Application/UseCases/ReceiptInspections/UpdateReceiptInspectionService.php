<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\ReceiptInspections;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\ReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections\UpdateReceiptInspectionServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateReceiptInspectionService implements UpdateReceiptInspectionServiceInterface
{
    public function __construct(private readonly ReceiptInspectionServiceInterface $receiptInspectionService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->receiptInspectionService->updateInspection($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
