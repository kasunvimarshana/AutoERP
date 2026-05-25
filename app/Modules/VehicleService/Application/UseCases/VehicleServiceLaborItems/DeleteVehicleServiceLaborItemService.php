<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\DeleteVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class DeleteVehicleServiceLaborItemService implements DeleteVehicleServiceLaborItemServiceInterface
{
    public function __construct(private readonly VehicleServiceLaborItemRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'VehicleServiceLaborItem not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}