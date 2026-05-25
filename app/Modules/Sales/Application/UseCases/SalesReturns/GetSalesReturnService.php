<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\SalesReturns;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\GetSalesReturnServiceInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class GetSalesReturnService implements GetSalesReturnServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'SalesReturn not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}