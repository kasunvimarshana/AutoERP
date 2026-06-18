<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseModel;
use Throwable;

final class GetWarehouseService
{
    public function execute(int|string $id, int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            $record = WarehouseModel::query()
                ->forTenant($tenantId, $organizationUnitId)
                ->with(['organizationUnit', 'defaultLocation', 'locations' => fn ($query) => $query->orderBy('path')->orderBy('name')])
                ->withCount('locations')
                ->find($id);

            if ($record === null) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse could not be loaded.'));
        }
    }
}
