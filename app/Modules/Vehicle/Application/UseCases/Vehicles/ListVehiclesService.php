<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\Vehicles;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\ListVehiclesServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleDefaults;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class ListVehiclesService implements ListVehiclesServiceInterface
{
    public function __construct(private readonly VehicleRepositoryInterface $vehicles)
    {
    }

    public function execute(array $filters): Result
    {
        try {
            $result = $this->vehicles->pageByFilters(
                isset($filters['tenant_id']) ? (int) $filters['tenant_id'] : null,
                isset($filters['organization_unit_id']) ? (int) $filters['organization_unit_id'] : null,
                isset($filters['vehicle_code']) ? trim((string) $filters['vehicle_code']) : null,
                isset($filters['vin']) ? trim((string) $filters['vin']) : null,
                isset($filters['license_plate']) ? trim((string) $filters['license_plate']) : null,
                isset($filters['status']) ? trim((string) $filters['status']) : null,
                max(
                    1,
                    (int) (
                        $filters['per_page']
                        ?? (int) config('vehicle.pagination.default_per_page', VehicleDefaults::DEFAULT_PER_PAGE)
                    ),
                ),
                max(1, (int) ($filters['page'] ?? VehicleDefaults::DEFAULT_PAGE)),
            );

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
