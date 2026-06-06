<?php

declare(strict_types=1);

namespace Modules\Item\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class ItemSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('item_categories')) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->defaultTenantId();
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);

            $this->seedCategories($tenantId, $organizationUnitId);
            $this->seedBrands($tenantId, $organizationUnitId);
        }, 3);
    }

    private function seedCategories(int $tenantId, ?int $organizationUnitId): void
    {
        foreach ([
            ['code' => 'GENERAL', 'name' => 'General Items'],
            ['code' => 'SERVICES', 'name' => 'Services'],
            ['code' => 'LABOUR', 'name' => 'Labour'],
        ] as $index => $category) {
            DB::table('item_categories')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'code' => $category['code'],
                ],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'parent_id' => null,
                    'name' => $category['name'],
                    'description' => 'Generic item master category.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedBrands(int $tenantId, ?int $organizationUnitId): void
    {
        DB::table('item_brands')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'code' => 'GENERIC',
            ],
            [
                'organization_unit_id' => $organizationUnitId,
                'name' => 'Generic',
                'description' => 'Generic item brand placeholder.',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function defaultTenantId(): int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($id === null) {
            throw new RuntimeException('Seed a tenant before running the Item module seeder.');
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
}
