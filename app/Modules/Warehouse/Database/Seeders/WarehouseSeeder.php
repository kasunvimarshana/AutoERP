<?php

declare(strict_types=1);

namespace Modules\Warehouse\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class WarehouseSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $warehouse = WarehouseModel::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'name' => 'Main Warehouse'],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'code' => 'MAIN',
                    'type' => 'standard',
                    'is_active' => true,
                    'is_default' => true,
                    'row_version' => 1,
                    'metadata' => ['seed_source' => 'warehouse_module'],
                ],
            );

            if (! Schema::hasTable('warehouse_locations')) {
                return;
            }

            foreach ([
                ['code' => 'RECEIVING', 'name' => 'Receiving', 'type' => 'staging', 'pickable' => false, 'receivable' => true],
                ['code' => 'DISPATCH', 'name' => 'Dispatch', 'type' => 'dispatch', 'pickable' => true, 'receivable' => false],
                ['code' => 'RETURNS', 'name' => 'Returns', 'type' => 'staging', 'pickable' => false, 'receivable' => true],
            ] as $location) {
                WarehouseLocationModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'warehouse_id' => $warehouse->getKey(),
                        'name' => $location['name'],
                    ],
                    [
                        'organization_unit_id' => $organizationUnit?->getKey(),
                        'parent_id' => null,
                        'code' => $location['code'],
                        'path' => '/'.strtolower($location['code']),
                        'depth' => 0,
                        'type' => $location['type'],
                        'is_active' => true,
                        'is_pickable' => $location['pickable'],
                        'is_receivable' => $location['receivable'],
                        'row_version' => 1,
                        'metadata' => ['seed_source' => 'warehouse_module'],
                    ],
                );
            }
        }, 3);
    }
}
