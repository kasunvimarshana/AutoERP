<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\ListVehicleDocumentsServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleDefaults;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class ListVehicleDocumentsService implements ListVehicleDocumentsServiceInterface
{
    public function __construct(private readonly VehicleDocumentRepositoryInterface $documents)
    {
    }

    public function execute(array $filters): Result
    {
        try {
            $result = $this->documents->pageByFilters(
                isset($filters['tenant_id']) ? (int) $filters['tenant_id'] : null,
                isset($filters['organization_unit_id']) ? (int) $filters['organization_unit_id'] : null,
                isset($filters['vehicle_id']) ? (int) $filters['vehicle_id'] : null,
                isset($filters['name']) ? trim((string) $filters['name']) : null,
                isset($filters['type']) ? trim((string) $filters['type']) : null,
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
