<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\SalesReturns;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\CreateSalesReturnServiceInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class CreateSalesReturnService implements CreateSalesReturnServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}