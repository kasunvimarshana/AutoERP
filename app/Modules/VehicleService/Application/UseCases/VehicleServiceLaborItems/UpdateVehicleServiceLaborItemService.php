<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\UpdateVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class UpdateVehicleServiceLaborItemService implements UpdateVehicleServiceLaborItemServiceInterface
{
    public function __construct(private readonly VehicleServiceLaborItemRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'VehicleServiceLaborItem not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}