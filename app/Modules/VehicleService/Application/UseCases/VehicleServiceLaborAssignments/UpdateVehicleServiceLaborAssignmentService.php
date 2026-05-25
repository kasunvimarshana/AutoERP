<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceLaborAssignments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\UpdateVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborAssignmentRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class UpdateVehicleServiceLaborAssignmentService implements UpdateVehicleServiceLaborAssignmentServiceInterface
{
    public function __construct(private readonly VehicleServiceLaborAssignmentRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'VehicleServiceLaborAssignment not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}