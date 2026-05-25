<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases\PurchaseOrderLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\GetPurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class GetPurchaseOrderLineService implements GetPurchaseOrderLineServiceInterface
{
    public function __construct(private readonly PurchaseOrderLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'PurchaseOrderLine not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}