<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceInspections;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\GetVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class GetVehicleServiceInspectionService implements GetVehicleServiceInspectionServiceInterface
{
    public function __construct(private readonly VehicleServiceInspectionRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'VehicleServiceInspection not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}