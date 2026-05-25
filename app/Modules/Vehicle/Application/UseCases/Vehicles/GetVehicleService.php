<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\Vehicles;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\GetVehicleServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class GetVehicleService implements GetVehicleServiceInterface
{
    public function __construct(private readonly VehicleRepositoryInterface $vehicles)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->vehicles->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleErrorCode::NOT_FOUND, 'Vehicle not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
