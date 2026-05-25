<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerVehicles;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\GetCustomerVehicleServiceInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class GetCustomerVehicleService implements GetCustomerVehicleServiceInterface
{
    public function __construct(private readonly CustomerVehicleRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'CustomerVehicle not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}