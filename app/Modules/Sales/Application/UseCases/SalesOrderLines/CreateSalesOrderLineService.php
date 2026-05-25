<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\SalesOrderLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\SalesOrderLines\CreateSalesOrderLineServiceInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class CreateSalesOrderLineService implements CreateSalesOrderLineServiceInterface
{
    public function __construct(private readonly SalesOrderLineRepositoryInterface $repository)
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