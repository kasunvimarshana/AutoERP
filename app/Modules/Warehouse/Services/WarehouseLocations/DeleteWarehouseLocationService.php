<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\WarehouseLocations;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDomainService;
use Throwable;

final class DeleteWarehouseLocationService
{
    public function __construct(private readonly WarehouseDomainService $domain) {}

    public function execute(int|string $id, int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            return DB::transaction(function () use ($id, $tenantId, $organizationUnitId): Result {
                $location = WarehouseLocationModel::query()
                    ->inExactScope($tenantId, $organizationUnitId)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();
                if (! $location instanceof WarehouseLocationModel) {
                    return Result::failure(new Error(WarehouseErrorCode::LOCATION_NOT_FOUND, 'Warehouse location not found.'));
                }

                WarehouseModel::query()->whereKey((int) $location->warehouse_id)->lockForUpdate()->firstOrFail();
                $this->domain->assertLocationCanBeDeleted($location);
                $location->is_default = false;
                $location->row_version = ((int) $location->row_version) + 1;
                $location->save();
                $location->delete();

                return Result::success(null);
            }, 3);
        } catch (InvalidArgumentException $exception) {
            return Result::failure(new Error(WarehouseErrorCode::UNSAFE_DELETE, $exception->getMessage()));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse location could not be deleted.'));
        }
    }
}
