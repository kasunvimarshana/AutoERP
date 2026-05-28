<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeRunningCharts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\CreateVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeRunningChartRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class CreateVehicleRentalLesseeRunningChartService implements CreateVehicleRentalLesseeRunningChartServiceInterface
{
    public function __construct(private readonly VehicleRentalLesseeRunningChartRepositoryInterface $repository)
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
            return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
