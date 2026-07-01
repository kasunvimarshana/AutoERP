<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDomainService;
use Throwable;

final class DeleteWarehouseService
{
    public function __construct(private readonly WarehouseDomainService $domain) {}

    public function execute(int|string $id, int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            return DB::transaction(function () use ($id, $tenantId, $organizationUnitId): Result {
                $warehouse = WarehouseModel::query()
                    ->inExactScope($tenantId, $organizationUnitId)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();
                if (! $warehouse instanceof WarehouseModel) {
                    return $this->missingResult();
                }

                $this->domain->assertWarehouseCanBeDeleted($warehouse);
                $warehouse->is_default = false;
                $warehouse->row_version = ((int) $warehouse->row_version) + 1;
                $warehouse->save();
                $warehouse->delete();

                return Result::success(null);
            }, 3);
        } catch (InvalidArgumentException $exception) {
            return Result::failure(new Error(WarehouseErrorCode::UNSAFE_DELETE, $exception->getMessage()));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse could not be deleted.'));
        }
    }

    private function missingResult(): Result
    {
        return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
    }
}
