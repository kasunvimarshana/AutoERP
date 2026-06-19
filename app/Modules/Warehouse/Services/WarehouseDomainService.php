<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class WarehouseDomainService
{
    /**
     * @return array<string, string>
     */
    public function locationTypes(): array
    {
        return [
            'zone' => 'Zone',
            'aisle' => 'Aisle',
            'rack' => 'Rack',
            'shelf' => 'Shelf',
            'bin' => 'Bin',
            'staging' => 'Staging',
            'dispatch' => 'Dispatch',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function warehouseTypes(): array
    {
        return [
            'standard' => 'Standard',
            'virtual' => 'Virtual',
            'transit' => 'Transit',
            'quarantine' => 'Quarantine',
        ];
    }

    public function lockScopeOwner(int $tenantId, ?int $organizationUnitId): void
    {
        if ($organizationUnitId === null) {
            TenantModel::query()
                ->whereKey($tenantId)
                ->lockForUpdate()
                ->firstOrFail();

            return;
        }

        OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function assertDefaultIsActive(bool $isDefault, bool $isActive, string $label): void
    {
        if ($isDefault && ! $isActive) {
            throw new InvalidArgumentException($label.' must be active before it can be the default.');
        }
    }

    public function assertWarehouseUnique(
        int $tenantId,
        ?int $organizationUnitId,
        string $name,
        ?string $code = null,
        ?int $excludeId = null,
    ): void {
        $nameQuery = WarehouseModel::query()
            ->withTrashed()
            ->inExactScope($tenantId, $organizationUnitId)
            ->where('name', $name);
        $this->excludeId($nameQuery, $excludeId);
        if ($nameQuery->exists()) {
            throw new InvalidArgumentException('Warehouse name already exists in this organization scope.');
        }

        $code = $this->nullableString($code);
        if ($code === null) {
            return;
        }

        $codeQuery = WarehouseModel::query()
            ->withTrashed()
            ->inExactScope($tenantId, $organizationUnitId)
            ->where('code', $code);
        $this->excludeId($codeQuery, $excludeId);
        if ($codeQuery->exists()) {
            throw new InvalidArgumentException('Warehouse code already exists in this organization scope.');
        }
    }

    public function assertLocationUnique(
        int $tenantId,
        int $warehouseId,
        string $name,
        ?string $code = null,
        ?int $excludeId = null,
    ): void {
        $nameQuery = WarehouseLocationModel::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('name', $name);
        $this->excludeId($nameQuery, $excludeId);
        if ($nameQuery->exists()) {
            throw new InvalidArgumentException('Location name already exists in this warehouse.');
        }

        $code = $this->nullableString($code);
        if ($code === null) {
            return;
        }

        $codeQuery = WarehouseLocationModel::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('code', $code);
        $this->excludeId($codeQuery, $excludeId);
        if ($codeQuery->exists()) {
            throw new InvalidArgumentException('Location code already exists in this warehouse.');
        }
    }

    public function assertWarehouseAccessible(WarehouseModel $warehouse, int $tenantId, ?int $organizationUnitId): void
    {
        if ((int) $warehouse->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Warehouse belongs to a different tenant.');
        }

        if ($organizationUnitId === null) {
            if ($warehouse->organization_unit_id !== null) {
                throw new InvalidArgumentException('Warehouse belongs to a different organization unit.');
            }

            return;
        }

        if ($warehouse->organization_unit_id !== null && (int) $warehouse->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Warehouse belongs to a different organization unit.');
        }
    }

    public function assertWarehouseManaged(WarehouseModel $warehouse, int $tenantId, ?int $organizationUnitId): void
    {
        if ((int) $warehouse->tenant_id !== $tenantId || $this->nullableInt($warehouse->organization_unit_id) !== $organizationUnitId) {
            throw new InvalidArgumentException('Warehouse scope cannot be changed or managed from this organization context.');
        }
    }

    public function assertLocationManaged(WarehouseLocationModel $location, int $tenantId, ?int $organizationUnitId): void
    {
        if ((int) $location->tenant_id !== $tenantId || $this->nullableInt($location->organization_unit_id) !== $organizationUnitId) {
            throw new InvalidArgumentException('Warehouse location scope cannot be changed or managed from this organization context.');
        }
    }

    public function clearOtherWarehouseDefaults(WarehouseModel $warehouse): void
    {
        WarehouseModel::query()
            ->inExactScope((int) $warehouse->tenant_id, $this->nullableInt($warehouse->organization_unit_id))
            ->whereKeyNot($warehouse->getKey())
            ->where('is_default', true)
            ->update([
                'is_default' => false,
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => now(),
            ]);
    }

    public function clearOtherLocationDefaults(WarehouseLocationModel $location): void
    {
        WarehouseLocationModel::query()
            ->where('warehouse_id', (int) $location->warehouse_id)
            ->whereKeyNot($location->getKey())
            ->where('is_default', true)
            ->update([
                'is_default' => false,
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => now(),
            ]);
    }

    public function resolveParent(
        WarehouseModel $warehouse,
        ?int $parentId,
        ?int $movingLocationId = null,
    ): ?WarehouseLocationModel {
        if ($parentId === null) {
            return null;
        }

        if ($movingLocationId !== null && $parentId === $movingLocationId) {
            throw new InvalidArgumentException('A warehouse location cannot be its own parent.');
        }

        $parent = WarehouseLocationModel::query()
            ->where('tenant_id', (int) $warehouse->tenant_id)
            ->where('warehouse_id', (int) $warehouse->getKey())
            ->find($parentId);
        if (! $parent instanceof WarehouseLocationModel) {
            throw new InvalidArgumentException('Parent location must belong to the same warehouse.');
        }

        if ($this->nullableInt($parent->organization_unit_id) !== $this->nullableInt($warehouse->organization_unit_id)) {
            throw new InvalidArgumentException('Parent location must match the warehouse organization scope.');
        }

        if ($movingLocationId !== null) {
            $ancestor = $parent;
            while ($ancestor instanceof WarehouseLocationModel) {
                if ((int) $ancestor->getKey() === $movingLocationId) {
                    throw new InvalidArgumentException('A warehouse location cannot be moved below one of its descendants.');
                }
                $ancestor = $ancestor->parent_id === null
                    ? null
                    : WarehouseLocationModel::query()
                        ->where('warehouse_id', (int) $warehouse->getKey())
                        ->find($ancestor->parent_id);
            }
        }

        if ((string) $parent->type === 'bin') {
            throw new InvalidArgumentException('Bin locations cannot contain child locations.');
        }

        return $parent;
    }

    /**
     * @return array{path: string, depth: int}
     */
    public function hierarchyAttributes(?WarehouseLocationModel $parent, string $name, ?string $code): array
    {
        $segment = $this->pathSegment($code ?: $name);
        if ($parent === null) {
            return ['path' => '/'.$segment, 'depth' => 0];
        }

        $parentPath = rtrim((string) $parent->path, '/');

        return [
            'path' => ($parentPath === '' ? '' : $parentPath).'/'.$segment,
            'depth' => ((int) $parent->depth) + 1,
        ];
    }

    public function updateDescendantHierarchy(
        WarehouseLocationModel $location,
        string $oldPath,
        int $oldDepth,
    ): void {
        $newPath = (string) $location->path;
        $newDepth = (int) $location->depth;
        if ($oldPath === '' || ($oldPath === $newPath && $oldDepth === $newDepth)) {
            return;
        }

        $descendants = WarehouseLocationModel::query()
            ->where('warehouse_id', (int) $location->warehouse_id)
            ->where('path', 'like', rtrim($oldPath, '/').'/%')
            ->orderBy('depth')
            ->orderBy('id')
            ->get();

        foreach ($descendants as $descendant) {
            $suffix = substr((string) $descendant->path, strlen(rtrim($oldPath, '/')));
            $descendant->path = rtrim($newPath, '/').$suffix;
            $descendant->depth = max(0, ((int) $descendant->depth) - $oldDepth + $newDepth);
            $descendant->row_version = ((int) $descendant->row_version) + 1;
            $descendant->save();
        }
    }

    public function assertWarehouseCanBeDeleted(WarehouseModel $warehouse): void
    {
        if ($warehouse->locations()->exists()) {
            throw new InvalidArgumentException('Warehouse cannot be deleted while locations are configured. Deactivate it instead.');
        }

        if ($this->hasReferences((int) $warehouse->tenant_id, (int) $warehouse->getKey(), $this->warehouseReferenceColumns())) {
            throw new InvalidArgumentException('Warehouse cannot be deleted while inventory or operational documents reference it. Deactivate it instead.');
        }
    }

    public function assertLocationCanBeDeleted(WarehouseLocationModel $location): void
    {
        if ($location->children()->exists()) {
            throw new InvalidArgumentException('Warehouse location cannot be deleted while child locations exist.');
        }

        if ($this->hasReferences((int) $location->tenant_id, (int) $location->getKey(), $this->locationReferenceColumns())) {
            throw new InvalidArgumentException('Warehouse location cannot be deleted while inventory or operational documents reference it. Deactivate it instead.');
        }
    }

    public function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    public function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function excludeId(Builder $query, ?int $excludeId): void
    {
        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }
    }

    private function pathSegment(string $value): string
    {
        $segment = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $value), '-'));

        return $segment !== '' ? $segment : 'location';
    }

    /**
     * @param  array<string, list<string>>  $references
     */
    private function hasReferences(int $tenantId, int $id, array $references): bool
    {
        foreach ($references as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $query = DB::table($table)->where($column, $id);
                if (Schema::hasColumn($table, 'tenant_id')) {
                    $query->where('tenant_id', $tenantId);
                }
                if (Schema::hasColumn($table, 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }
                if ($query->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, list<string>>
     */
    private function warehouseReferenceColumns(): array
    {
        return [
            'inventory_stock_balances' => ['warehouse_id'],
            'inventory_movements' => ['warehouse_id'],
            'inventory_serial_numbers' => ['warehouse_id'],
            'inventory_reservations' => ['warehouse_id'],
            'inventory_allocations' => ['warehouse_id'],
            'inventory_adjustments' => ['warehouse_id'],
            'inventory_stock_counts' => ['warehouse_id'],
            'inventory_stock_state_changes' => ['warehouse_id'],
            'inventory_transfers' => ['from_warehouse_id', 'to_warehouse_id'],
            'inventory_valuation_layers' => ['warehouse_id'],
            'purchase_orders' => ['warehouse_id'],
            'goods_receipt_notes' => ['warehouse_id'],
            'purchase_returns' => ['warehouse_id'],
            'sales_orders' => ['warehouse_id'],
            'sales_allocations' => ['warehouse_id'],
            'sales_deliveries' => ['warehouse_id'],
            'sales_returns' => ['warehouse_id'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function locationReferenceColumns(): array
    {
        return [
            'inventory_stock_balances' => ['warehouse_location_id'],
            'inventory_movements' => ['warehouse_location_id'],
            'inventory_serial_numbers' => ['warehouse_location_id'],
            'inventory_reservations' => ['warehouse_location_id'],
            'inventory_allocations' => ['warehouse_location_id'],
            'inventory_adjustments' => ['warehouse_location_id'],
            'inventory_stock_counts' => ['warehouse_location_id'],
            'inventory_stock_state_changes' => ['warehouse_location_id'],
            'inventory_transfers' => ['from_warehouse_location_id', 'to_warehouse_location_id'],
            'inventory_valuation_layers' => ['warehouse_location_id'],
            'purchase_orders' => ['warehouse_location_id'],
            'goods_receipt_notes' => ['warehouse_location_id'],
            'purchase_returns' => ['warehouse_location_id'],
            'sales_orders' => ['warehouse_location_id'],
            'sales_allocations' => ['warehouse_location_id'],
            'sales_deliveries' => ['warehouse_location_id'],
            'sales_returns' => ['warehouse_location_id'],
        ];
    }
}
