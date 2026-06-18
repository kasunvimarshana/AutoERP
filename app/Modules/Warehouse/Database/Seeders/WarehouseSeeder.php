<?php

declare(strict_types=1);

namespace Modules\Warehouse\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseAuthorizationService;

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
            $tenantId = (int) $tenant->getKey();
            $organizationUnitId = $organizationUnit?->getKey();
            $this->seedPermissions($tenantId);

            $warehouse = WarehouseModel::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => 'Main Warehouse'],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'code' => 'MAIN',
                    'type' => 'standard',
                    'is_active' => true,
                    'is_default' => true,
                    'row_version' => 1,
                    'metadata' => ['seed_source' => 'warehouse_module'],
                ],
            );

            WarehouseModel::query()
                ->where('tenant_id', $tenantId)
                ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where('organization_unit_id', $organizationUnitId))
                ->whereKeyNot($warehouse->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false, 'updated_at' => now()]);

            if (! Schema::hasTable('warehouse_locations')) {
                return;
            }

            foreach ([
                ['code' => 'RECEIVING', 'name' => 'Receiving', 'type' => 'staging', 'pickable' => false, 'receivable' => true, 'default' => true],
                ['code' => 'DISPATCH', 'name' => 'Dispatch', 'type' => 'dispatch', 'pickable' => true, 'receivable' => false],
                ['code' => 'RETURNS', 'name' => 'Returns', 'type' => 'staging', 'pickable' => false, 'receivable' => true],
            ] as $location) {
                $saved = WarehouseLocationModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'warehouse_id' => $warehouse->getKey(),
                        'name' => $location['name'],
                    ],
                    [
                        'organization_unit_id' => $organizationUnitId,
                        'parent_id' => null,
                        'code' => $location['code'],
                        'path' => '/'.strtolower($location['code']),
                        'depth' => 0,
                        'type' => $location['type'],
                        'is_active' => true,
                        'is_pickable' => $location['pickable'],
                        'is_receivable' => $location['receivable'],
                        'is_default' => (bool) ($location['default'] ?? false),
                        'row_version' => 1,
                        'metadata' => ['seed_source' => 'warehouse_module'],
                    ],
                );

                if ((bool) ($location['default'] ?? false)) {
                    WarehouseLocationModel::query()
                        ->where('warehouse_id', $warehouse->getKey())
                        ->whereKeyNot($saved->getKey())
                        ->where('is_default', true)
                        ->update(['is_default' => false, 'updated_at' => now()]);
                }
            }
        }, 3);
    }

    private function seedPermissions(int $tenantId): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $guard = (string) config('auth.defaults.guard', 'web');
        foreach (WarehouseAuthorizationService::descriptions() as $name => $description) {
            DB::table('permissions')->updateOrInsert(
                ['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard],
                [
                    'organization_unit_id' => null,
                    'module' => 'Warehouse',
                    'description' => $description,
                    'row_version' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
