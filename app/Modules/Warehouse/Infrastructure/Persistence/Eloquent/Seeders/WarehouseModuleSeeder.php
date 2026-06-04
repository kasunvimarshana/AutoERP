<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Seeders;

use Database\Seeders\Concerns\SeedsAutoErpData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WarehouseModuleSeeder extends Seeder
{
    use SeedsAutoErpData;

    public function run(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        $tenantId = $this->defaultTenantId();
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);

            $mainWarehouseId = $this->warehouse($tenantId, $organizationUnitId, 'MAIN', 'Main Warehouse', 'standard', true, true);
            $serviceWarehouseId = $this->warehouse($tenantId, $organizationUnitId, 'SERVICE', 'Service Store', 'standard', false, true);
            $quarantineWarehouseId = $this->warehouse($tenantId, $organizationUnitId, 'QC-HOLD', 'Quality Hold Warehouse', 'quarantine', false, true);
            $this->warehouse($tenantId, $organizationUnitId, 'LEGACY-WH', 'Legacy Closed Warehouse', 'standard', false, false);

            $this->location($tenantId, $organizationUnitId, $mainWarehouseId, null, 'MAIN-ZONE-A', 'Main Zone A', 'zone', '/MAIN-ZONE-A', 0, true, true, true);
            $zoneId = (int) DB::table('warehouse_locations')
                ->where('tenant_id', $tenantId)
                ->where('warehouse_id', $mainWarehouseId)
                ->where('code', 'MAIN-ZONE-A')
                ->value('id');
            $this->location($tenantId, $organizationUnitId, $mainWarehouseId, $zoneId, 'MAIN-BIN', 'Main Bin', 'bin', '/MAIN-ZONE-A/MAIN-BIN', 1, true, true, true);
            $this->location($tenantId, $organizationUnitId, $mainWarehouseId, $zoneId, 'MAIN-STAGE', 'Dispatch Staging', 'staging', '/MAIN-ZONE-A/MAIN-STAGE', 1, true, false, true);

            $this->location($tenantId, $organizationUnitId, $serviceWarehouseId, null, 'SERVICE-BIN', 'Service Bin', 'bin', '/SERVICE-BIN', 0, true, true, true);
            $this->location($tenantId, $organizationUnitId, $quarantineWarehouseId, null, 'QC-REJECT', 'Rejected Stock', 'bin', '/QC-REJECT', 0, true, false, true);
        }, 3);
    }

    private function warehouse(
        int $tenantId,
        ?int $organizationUnitId,
        string $code,
        string $name,
        string $type,
        bool $default,
        bool $active,
    ): int {
        $this->upsert('warehouses', [
            'tenant_id' => $tenantId,
            'name' => $name,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('warehouse_module', $active ? 'active' : 'inactive'),
            'code' => $code,
            'image_path' => null,
            'type' => $type,
            'is_active' => $active,
            'is_default' => $default,
        ]);

        return (int) DB::table('warehouses')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->value('id');
    }

    private function location(
        int $tenantId,
        ?int $organizationUnitId,
        int $warehouseId,
        ?int $parentId,
        string $code,
        string $name,
        string $type,
        string $path,
        int $depth,
        bool $pickable,
        bool $receivable,
        bool $active,
    ): void {
        $this->upsert('warehouse_locations', [
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'name' => $name,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('warehouse_module', 'location'),
            'parent_id' => $parentId,
            'code' => $code,
            'path' => $path,
            'depth' => $depth,
            'type' => $type,
            'is_active' => $active,
            'is_pickable' => $pickable,
            'is_receivable' => $receivable,
            'capacity' => $type === 'bin' ? '1000.0000' : null,
        ]);
    }
}
