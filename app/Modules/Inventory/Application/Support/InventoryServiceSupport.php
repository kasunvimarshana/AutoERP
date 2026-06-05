<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InventoryServiceSupport
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function tenantId(array $payload): int
    {
        $tenantId = $payload['tenant_id'] ?? null;
        if ($tenantId === null || (int) $tenantId <= 0) {
            throw ValidationException::withMessages([
                'tenant_id' => ['Tenant ID is required.'],
            ]);
        }

        return (int) $tenantId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function organizationUnitId(array $payload): ?int
    {
        return isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function normalizeLines(array $payload, bool $warehouseRequired = true): array
    {
        if (! isset($payload['lines']) || ! is_array($payload['lines']) || $payload['lines'] === []) {
            throw ValidationException::withMessages([
                'lines' => ['At least one inventory line is required.'],
            ]);
        }

        $lines = [];
        foreach (array_values($payload['lines']) as $index => $line) {
            if (! is_array($line)) {
                throw ValidationException::withMessages([
                    "lines.$index" => ['Inventory line must be an object.'],
                ]);
            }

            $quantity = (float) ($line['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => ['Quantity must be greater than zero.'],
                ]);
            }

            $warehouseId = $line['warehouse_id'] ?? $payload['warehouse_id'] ?? null;
            if ($warehouseRequired && $warehouseId === null) {
                throw ValidationException::withMessages([
                    "lines.$index.warehouse_id" => ['Warehouse ID is required.'],
                ]);
            }

            $itemId = $line['item_id'] ?? null;
            $uomId = $line['uom_id'] ?? $line['transaction_uom_id'] ?? null;
            if ($itemId === null || $uomId === null) {
                throw ValidationException::withMessages([
                    "lines.$index.item_id" => ['Item ID and UOM ID are required.'],
                ]);
            }

            $lines[] = [
                ...$line,
                'item_id' => (int) $itemId,
                'uom_id' => (int) $uomId,
                'warehouse_id' => $warehouseId === null ? null : (int) $warehouseId,
                'location_id' => isset($line['location_id'])
                    ? (int) $line['location_id']
                    : (isset($payload['location_id']) ? (int) $payload['location_id'] : null),
                'variant_id' => isset($line['variant_id']) ? (int) $line['variant_id'] : null,
                'batch_id' => isset($line['batch_id']) ? (int) $line['batch_id'] : null,
                'serial_id' => isset($line['serial_id']) ? (int) $line['serial_id'] : null,
                'quantity' => $quantity,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function validateReferences(int $tenantId, array $lines): void
    {
        $this->assertTenantRows('items', $tenantId, $this->ids($lines, 'item_id'), 'item_id');
        $this->assertTenantRows('unit_of_measures', $tenantId, $this->ids($lines, 'uom_id'), 'uom_id');
        $this->assertTenantRows('warehouses', $tenantId, $this->ids($lines, 'warehouse_id'), 'warehouse_id');
        $this->assertTenantRows('warehouse_locations', $tenantId, $this->ids($lines, 'location_id'), 'location_id');
        $this->assertTenantRows('item_variants', $tenantId, $this->ids($lines, 'variant_id'), 'variant_id');
        $this->assertTenantRows('batches', $tenantId, $this->ids($lines, 'batch_id'), 'batch_id');
        $this->assertTenantRows('serials', $tenantId, $this->ids($lines, 'serial_id'), 'serial_id');
    }

    /**
     * @param  array<int, int|null>  $warehouseIds
     * @param  array<int, int|null>  $locationIds
     */
    public function validateWarehouseScope(int $tenantId, array $warehouseIds, array $locationIds = []): void
    {
        $this->assertTenantRows('warehouses', $tenantId, array_values(array_filter(array_map(
            fn (?int $id): ?int => $id === null ? null : (int) $id,
            $warehouseIds,
        ))), 'warehouse_id');
        $this->assertTenantRows('warehouse_locations', $tenantId, array_values(array_filter(array_map(
            fn (?int $id): ?int => $id === null ? null : (int) $id,
            $locationIds,
        ))), 'location_id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, int>
     */
    public function itemBaseUomMap(int $tenantId, array $lines): array
    {
        $items = DB::table('items')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $this->ids($lines, 'item_id'))
            ->pluck('base_uom_id', 'id');

        return $items->map(fn ($value): int => (int) $value)->all();
    }

    public function convertToBase(int $tenantId, int $itemId, int $fromUomId, int $baseUomId, float $quantity): float
    {
        if ($fromUomId === $baseUomId) {
            return $this->roundQuantity($quantity);
        }

        $conversion = DB::table('uom_conversions')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($itemId): void {
                $query->where('item_id', $itemId)->orWhereNull('item_id');
            })
            ->where(function (Builder $query) use ($fromUomId, $baseUomId): void {
                $query
                    ->where(function (Builder $direct) use ($fromUomId, $baseUomId): void {
                        $direct->where('from_uom_id', $fromUomId)->where('to_uom_id', $baseUomId);
                    })
                    ->orWhere(function (Builder $reverse) use ($fromUomId, $baseUomId): void {
                        $reverse
                            ->where('from_uom_id', $baseUomId)
                            ->where('to_uom_id', $fromUomId)
                            ->where('is_bidirectional', true);
                    });
            })
            ->orderByRaw('case when item_id is null then 1 else 0 end')
            ->first();

        if ($conversion === null) {
            throw ValidationException::withMessages([
                'uom_id' => ['No active UOM conversion exists for the selected item and base UOM.'],
            ]);
        }

        $factor = (float) $conversion->factor;
        if ((int) $conversion->from_uom_id === $fromUomId) {
            return $this->roundQuantity($quantity * $factor);
        }

        if ($factor <= 0) {
            throw ValidationException::withMessages([
                'uom_id' => ['UOM conversion factor must be greater than zero.'],
            ]);
        }

        return $this->roundQuantity($quantity / $factor);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{on_hand_quantity: float, reserved_quantity: float, available_quantity: float}
     */
    public function availability(array $criteria): array
    {
        $tenantId = $this->tenantId($criteria);
        $row = DB::table('stock_levels')
            ->selectRaw(
                'coalesce(sum(quantity_on_hand), 0) as on_hand, '
                .'coalesce(sum(quantity_reserved), 0) as reserved, '
                .'coalesce(sum(quantity_blocked), 0) as blocked, '
                .'coalesce(sum(quantity_damaged), 0) as damaged'
            )
            ->where('tenant_id', $tenantId)
            ->tap(fn (Builder $query) => $this->applyStockFilters($query, $criteria))
            ->first();

        $onHand = (float) ($row->on_hand ?? 0);
        $reserved = (float) ($row->reserved ?? 0);
        $blocked = (float) ($row->blocked ?? 0);
        $damaged = (float) ($row->damaged ?? 0);

        return [
            'on_hand_quantity' => $this->roundQuantity($onHand),
            'reserved_quantity' => $this->roundQuantity($reserved),
            'available_quantity' => $this->roundQuantity($onHand - $reserved - $blocked - $damaged),
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public function adjustStockLevel(
        int $tenantId,
        ?int $organizationUnitId,
        array $line,
        int $baseUomId,
        float $baseQuantity,
        string $direction,
        float $reservedDelta = 0,
        ?float $unitCost = null,
    ): array {
        $query = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('item_id', (int) $line['item_id'])
            ->where('warehouse_id', (int) $line['warehouse_id'])
            ->where('condition', (string) ($line['condition'] ?? 'good'))
            ->lockForUpdate();

        $this->whereNullable($query, 'variant_id', $line['variant_id'] ?? null);
        $this->whereNullable($query, 'location_id', $line['location_id'] ?? null);
        $this->whereNullable($query, 'batch_id', $line['batch_id'] ?? null);
        $this->whereNullable($query, 'serial_id', $line['serial_id'] ?? null);

        $level = $query->first();
        if ($level === null) {
            if ($direction === 'OUT' || $reservedDelta < 0) {
                throw ValidationException::withMessages([
                    'stock' => ['Insufficient stock for the requested movement.'],
                ]);
            }

            $id = DB::table('stock_levels')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'item_id' => (int) $line['item_id'],
                'variant_id' => $line['variant_id'] ?? null,
                'warehouse_id' => (int) $line['warehouse_id'],
                'location_id' => $line['location_id'] ?? null,
                'batch_id' => $line['batch_id'] ?? null,
                'serial_id' => $line['serial_id'] ?? null,
                'base_uom_id' => $baseUomId,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'quantity_blocked' => 0,
                'quantity_damaged' => 0,
                'quantity_in_transit' => 0,
                'unit_cost' => $unitCost,
                'last_movement_at' => now(),
                'condition' => (string) ($line['condition'] ?? 'good'),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $level = DB::table('stock_levels')->where('id', $id)->lockForUpdate()->first();
        }

        $onHand = (float) $level->quantity_on_hand;
        $reserved = (float) $level->quantity_reserved;
        $nextOnHand = $direction === 'IN' ? $onHand + $baseQuantity : $onHand - $baseQuantity;
        $nextReserved = $reserved + $reservedDelta;

        if ($nextOnHand < -0.0001 || $nextReserved < -0.0001 || $nextReserved > $nextOnHand + 0.0001) {
            throw ValidationException::withMessages([
                'stock' => ['Insufficient stock for the requested movement.'],
            ]);
        }

        DB::table('stock_levels')
            ->where('id', (int) $level->id)
            ->update([
                'quantity_on_hand' => $this->roundQuantity($nextOnHand),
                'quantity_reserved' => $this->roundQuantity($nextReserved),
                'unit_cost' => $unitCost ?? $level->unit_cost,
                'last_movement_at' => now(),
                'row_version' => ((int) $level->row_version) + 1,
                'updated_at' => now(),
            ]);

        return [
            'level_id' => (int) $level->id,
            'balance_quantity' => $this->roundQuantity($nextOnHand),
            'reserved_quantity' => $this->roundQuantity($nextReserved),
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public function stockCriteriaFromLine(int $tenantId, array $line): array
    {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? null,
            'warehouse_id' => $line['warehouse_id'] ?? null,
            'location_id' => $line['location_id'] ?? null,
            'item_id' => $line['item_id'] ?? null,
            'variant_id' => $line['variant_id'] ?? null,
            'batch_id' => $line['batch_id'] ?? null,
            'serial_id' => $line['serial_id'] ?? null,
            'condition' => $line['condition'] ?? 'good',
        ];
    }

    public function whereNullable(Builder $query, string $column, mixed $value): void
    {
        $value === null ? $query->whereNull($column) : $query->where($column, $value);
    }

    public function roundQuantity(float $quantity): float
    {
        return round($quantity, 4);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, int>
     */
    private function ids(array $lines, string $key): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn (array $line): ?int => isset($line[$key]) ? (int) $line[$key] : null, $lines),
            fn (?int $id): bool => $id !== null && $id > 0,
        )));
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function assertTenantRows(string $table, int $tenantId, array $ids, string $field): void
    {
        if ($ids === []) {
            return;
        }

        $query = DB::table($table)->where('tenant_id', $tenantId)->whereIn('id', $ids);
        if (DB::getSchemaBuilder()->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($query->count() !== count($ids)) {
            throw ValidationException::withMessages([
                $field => ["One or more selected $field records do not belong to the active tenant."],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function applyStockFilters(Builder $query, array $criteria): void
    {
        foreach ([
            'organization_unit_id',
            'warehouse_id',
            'location_id',
            'item_id',
            'variant_id',
            'batch_id',
            'serial_id',
            'condition',
        ] as $field) {
            if (array_key_exists($field, $criteria) && $criteria[$field] !== null) {
                $query->where($field, $criteria[$field]);
            }
        }
    }
}
