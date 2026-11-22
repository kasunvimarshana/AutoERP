<?php

declare(strict_types=1);

namespace Modules\UOM\Services\UnitOfMeasures;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Throwable;

final class DeleteUnitOfMeasureService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function execute(int|string $id, ?int $expectedVersion = null): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $current = $this->repository->findByIdInTenant($id, $tenantId);
            if ($current === null) {
                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UnitOfMeasure not found.'));
            }

            if ($expectedVersion !== null && (int) $current->get('row_version', 1) !== $expectedVersion) {
                return Result::failure(new Error(
                    UomErrorCode::CONFLICT,
                    'Unit of measure was changed by another transaction. Reload before deleting.',
                ));
            }

            if ($this->isReferenced((int) $id, $tenantId)) {
                return Result::failure(new Error(
                    UomErrorCode::CONFLICT,
                    'Unit of measure cannot be deleted while referenced by items, conversions, or transactions.',
                ));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function isReferenced(int $unitId, int $tenantId): bool
    {
        $references = [
            'uom_conversions' => ['from_uom_id', 'to_uom_id'],
            'items' => ['base_uom_id', 'uom_id', 'purchase_uom_id'],
            'item_units' => ['uom_id'],
            'sale_lines' => ['uom_id'],
            'purchase_order_lines' => ['uom_id', 'ordered_uom_id', 'base_uom_id'],
            'goods_receipt_note_lines' => ['uom_id'],
            'purchase_return_lines' => ['uom_id'],
            'invoice_lines' => ['uom_id'],
            'inventory_stock_balances' => ['uom_id'],
            'inventory_movements' => ['uom_id'],
            'vehicle_service_job_lines' => ['uom_id'],
        ];

        foreach ($references as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $validColumns = array_filter($columns, fn (string $col): bool => Schema::hasColumn($table, $col));
            if ($validColumns === []) {
                continue;
            }

            $query = DB::table($table)->where('tenant_id', $tenantId);
            $query->where(function ($sub) use ($validColumns, $unitId): void {
                $first = true;
                foreach ($validColumns as $column) {
                    if ($first) {
                        $sub->where($column, $unitId);
                        $first = false;
                    } else {
                        $sub->orWhere($column, $unitId);
                    }
                }
            });

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }
}
