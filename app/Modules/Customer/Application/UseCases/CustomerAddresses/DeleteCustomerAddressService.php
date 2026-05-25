<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerAddresses;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Repositories\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class DeleteCustomerAddressService implements DeleteCustomerAddressServiceInterface
{
    public function __construct(private readonly CustomerAddressRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'CustomerAddress not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}