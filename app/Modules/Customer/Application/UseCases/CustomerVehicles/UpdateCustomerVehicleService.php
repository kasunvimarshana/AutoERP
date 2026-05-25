<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerVehicles;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\UpdateCustomerVehicleServiceInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class UpdateCustomerVehicleService implements UpdateCustomerVehicleServiceInterface
{
    public function __construct(private readonly CustomerVehicleRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'CustomerVehicle not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}