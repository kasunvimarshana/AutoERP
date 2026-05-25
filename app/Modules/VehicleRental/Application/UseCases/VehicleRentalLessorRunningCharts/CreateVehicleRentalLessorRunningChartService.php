<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLessorRunningCharts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\CreateVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorRunningChartRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class CreateVehicleRentalLessorRunningChartService implements CreateVehicleRentalLessorRunningChartServiceInterface
{
    public function __construct(private readonly VehicleRentalLessorRunningChartRepositoryInterface $repository)
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