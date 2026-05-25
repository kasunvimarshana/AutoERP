<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerVehicles;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\DeleteCustomerVehicleServiceInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class DeleteCustomerVehicleService implements DeleteCustomerVehicleServiceInterface
{
    public function __construct(private readonly CustomerVehicleRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'CustomerVehicle not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}