<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceNonInventoryItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\CreateVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceNonInventoryItemRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class CreateVehicleServiceNonInventoryItemService implements CreateVehicleServiceNonInventoryItemServiceInterface
{
    public function __construct(private readonly VehicleServiceNonInventoryItemRepositoryInterface $repository)
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
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}