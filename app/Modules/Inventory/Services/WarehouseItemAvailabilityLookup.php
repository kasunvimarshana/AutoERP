<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Enums\BatchStatus;

final class WarehouseItemAvailabilityLookup
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @param list<int> $itemIds
     * @return array<int, string>
     */
    public function availableByItemIds(
        array $itemIds,
        int $tenantId,
        ?int $organizationUnitId,
        int $warehouseId,
        ?int $warehouseLocationId = null,
    ): array {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if ($itemIds === []) {
            return [];
        }

        $query = DB::table('inventory_stock_balances')
            ->selectRaw('item_id, SUM(quantity_available) as available_quantity')
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id');

        if ($warehouseLocationId !== null) {
            $query->where('warehouse_location_id', $warehouseLocationId);
        }

        if ($organizationUnitId === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
        }

        return $query->get()->mapWithKeys(fn ($row): array => [
            (int) $row->item_id => $this->math->normalize((string) $row->available_quantity),
        ])->all();
    }

    /** @param list<int> $itemIds
     * @return array<int, list<array{batch_number: string, lot_number: ?string, available_quantity: string}>>
     */
    public function availableBatchesByItemIds(
        array $itemIds,
        int $tenantId,
        ?int $organizationUnitId,
        int $warehouseId,
        ?int $warehouseLocationId = null,
    ): array {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if ($itemIds === []) {
            return [];
        }

        $query = DB::table('inventory_batches as batches')
            ->join('inventory_stock_balances as balances', 'balances.batch_id', '=', 'batches.id')
            ->select('batches.id', 'batches.item_id', 'batches.batch_number', 'batches.lot_number')
            ->selectRaw('SUM(balances.quantity_available) as available_quantity')
            ->where('batches.tenant_id', $tenantId)
            ->where('balances.tenant_id', $tenantId)
            ->where('balances.warehouse_id', $warehouseId)
            ->whereIn('batches.item_id', $itemIds)
            ->where('batches.status', BatchStatus::Active->value)
            ->whereNull('batches.deleted_at')
            ->where(static function ($expiry): void {
                $expiry->whereNull('batches.expiry_date')
                    ->orWhereDate('batches.expiry_date', '>=', now()->toDateString());
            })
            ->groupBy('batches.id', 'batches.item_id', 'batches.batch_number', 'batches.lot_number')
            ->havingRaw('SUM(balances.quantity_available) > 0')
            ->orderBy('batches.batch_number');

        if ($warehouseLocationId !== null) {
            $query->where('balances.warehouse_location_id', $warehouseLocationId);
        }

        if ($organizationUnitId === null) {
            $query->whereNull('balances.organization_unit_id');
        } else {
            $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('balances.organization_unit_id')
                    ->orWhere('balances.organization_unit_id', $organizationUnitId);
            });
        }

        return $query->get()->groupBy('item_id')->map(fn ($batches): array => $batches
            ->map(fn ($batch): array => [
                'batch_number' => (string) $batch->batch_number,
                'lot_number' => $batch->lot_number === null ? null : (string) $batch->lot_number,
                'available_quantity' => $this->math->normalize((string) $batch->available_quantity),
            ])->values()->all())->all();
    }
}
