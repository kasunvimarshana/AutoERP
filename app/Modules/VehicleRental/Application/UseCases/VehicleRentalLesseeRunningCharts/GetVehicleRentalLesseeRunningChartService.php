<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeRunningCharts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\GetVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeRunningChartRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class GetVehicleRentalLesseeRunningChartService implements GetVehicleRentalLesseeRunningChartServiceInterface
{
    public function __construct(private readonly VehicleRentalLesseeRunningChartRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'VehicleRentalLesseeRunningChart not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}