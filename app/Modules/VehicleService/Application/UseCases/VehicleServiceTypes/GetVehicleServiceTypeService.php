<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceTypes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\GetVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceTypeRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class GetVehicleServiceTypeService implements GetVehicleServiceTypeServiceInterface
{
    public function __construct(private readonly VehicleServiceTypeRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'VehicleServiceType not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}