<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\SalesOrders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\SalesOrders\UpdateSalesOrderServiceInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class UpdateSalesOrderService implements UpdateSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'SalesOrder not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}