<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\Customers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\Customers\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class DeleteCustomerService implements DeleteCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'Customer not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}