<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\UseCases\VehicleServiceNonInventoryItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\ListVehicleServiceNonInventoryItemsServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceNonInventoryItemRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceDefaults;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class ListVehicleServiceNonInventoryItemsService implements ListVehicleServiceNonInventoryItemsServiceInterface
{
    public function __construct(private readonly VehicleServiceNonInventoryItemRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : VehicleServiceDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('vehicle_service.pagination.max_per_page', VehicleServiceDefaults::MAX_PER_PAGE))
                : (int) config('vehicle_service.pagination.default_per_page', VehicleServiceDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}