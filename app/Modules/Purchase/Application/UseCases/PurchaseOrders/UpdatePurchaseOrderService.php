<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases\PurchaseOrders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class UpdatePurchaseOrderService implements UpdatePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'PurchaseOrder not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}