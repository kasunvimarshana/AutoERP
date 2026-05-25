<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceInspectionLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\GetVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionLineRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class GetVehicleServiceInspectionLineService implements GetVehicleServiceInspectionLineServiceInterface
{
    public function __construct(private readonly VehicleServiceInspectionLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'VehicleServiceInspectionLine not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}