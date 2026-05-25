<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\CreateVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class CreateVehicleServiceLaborItemService implements CreateVehicleServiceLaborItemServiceInterface
{
    public function __construct(private readonly VehicleServiceLaborItemRepositoryInterface $repository)
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
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}