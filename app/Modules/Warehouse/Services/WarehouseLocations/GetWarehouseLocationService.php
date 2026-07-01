<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\WarehouseLocations;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Throwable;

final class GetWarehouseLocationService
{
    public function execute(int|string $id, int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            $record = WarehouseLocationModel::query()
                ->forTenant($tenantId, $organizationUnitId)
                ->with(['warehouse', 'parent', 'organizationUnit'])
                ->find($id);

            if ($record === null) {
                return $this->missingResult();
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse location could not be loaded.'));
        }
    }

    private function missingResult(): Result
    {
        return Result::failure(new Error(WarehouseErrorCode::LOCATION_NOT_FOUND, 'Warehouse location not found.'));
    }
}
