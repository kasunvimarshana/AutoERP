<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerVehicles;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\CreateCustomerVehicleServiceInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class CreateCustomerVehicleService implements CreateCustomerVehicleServiceInterface
{
    public function __construct(private readonly CustomerVehicleRepositoryInterface $repository)
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
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}