<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases\PurchaseReturnLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines\DeletePurchaseReturnLineServiceInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class DeletePurchaseReturnLineService implements DeletePurchaseReturnLineServiceInterface
{
    public function __construct(private readonly PurchaseReturnLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'PurchaseReturnLine not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}