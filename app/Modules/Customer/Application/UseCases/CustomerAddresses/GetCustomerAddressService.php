<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerAddresses;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\GetCustomerAddressServiceInterface;
use Modules\Customer\Application\Repositories\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class GetCustomerAddressService implements GetCustomerAddressServiceInterface
{
    public function __construct(private readonly CustomerAddressRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'CustomerAddress not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}