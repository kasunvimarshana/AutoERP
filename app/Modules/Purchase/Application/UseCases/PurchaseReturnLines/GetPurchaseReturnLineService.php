<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases\PurchaseReturnLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\GetPurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class GetPurchaseReturnLineService implements GetPurchaseReturnLineServiceInterface
{
    public function __construct(private readonly PurchaseReturnLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'PurchaseReturnLine not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}