<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceJobCards;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\GetVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class GetVehicleServiceJobCardService implements GetVehicleServiceJobCardServiceInterface
{
    public function __construct(private readonly VehicleServiceJobCardRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'VehicleServiceJobCard not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}