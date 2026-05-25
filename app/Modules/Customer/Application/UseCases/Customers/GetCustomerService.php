<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\Customers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\Customers\GetCustomerServiceInterface;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class GetCustomerService implements GetCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'Customer not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}