<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Illuminate\Support\Facades\DB;
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
                return $this->missingResult($id, $tenantId);
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse could not be loaded.'));
        }
    }

    private function missingResult(int|string $id, int $tenantId): Result
    {
        $owner = DB::table('warehouses')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first(['tenant_id']);

        if ($owner !== null && (int) $owner->tenant_id !== $tenantId) {
            return Result::failure(new Error(WarehouseErrorCode::SCOPE_MISMATCH, 'Warehouse belongs to another tenant.'));
        }

        return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
    }
}
