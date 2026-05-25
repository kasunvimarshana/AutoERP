<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\SalesOrderLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\GetSalesOrderLineServiceInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class GetSalesOrderLineService implements GetSalesOrderLineServiceInterface
{
    public function __construct(private readonly SalesOrderLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'SalesOrderLine not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}