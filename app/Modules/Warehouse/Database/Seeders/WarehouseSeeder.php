<?php

declare(strict_types=1);

namespace Modules\Warehouse\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->defaultTenantId();
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);

            DB::table('warehouses')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'code' => 'MAIN',
                ],
                [
                    'image_path' => null,
                    'is_active' => true,
                    'is_default' => true,
                    'metadata' => $this->json(['seed_source' => 'warehouse_module']),
                    'name' => 'Main Warehouse',
                    'organization_unit_id' => $organizationUnitId,
                    'row_version' => 1,
                    'type' => 'standard',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }, 3);
    }

    private function defaultTenantId(): int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($id === null) {
            throw new RuntimeException('Seed a tenant before running the Warehouse module seeder.');
        }

        return (int) $id;
    }

    private function defaultOrganizationUnitId(int $tenantId): ?int
    {
        return DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * @param  array<string,mixed>  $value
     */
    private function json(array $value): string
    {
        return (string) json_encode($value, JSON_THROW_ON_ERROR);
    }
}
