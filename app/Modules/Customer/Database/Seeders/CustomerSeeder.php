<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('customer_categories')) {
            return;
        }

        $tenantId = $this->defaultTenantId();
        $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);

        foreach ([
            ['code' => 'GENERAL', 'name' => 'General Customers'],
            ['code' => 'RETAIL', 'name' => 'Retail Customers'],
            ['code' => 'WHOLESALE', 'name' => 'Wholesale Customers'],
            ['code' => 'CORPORATE', 'name' => 'Corporate Customers'],
        ] as $index => $category) {
            DB::table('customer_categories')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => $category['code']],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'parent_id' => null,
                    'name' => $category['name'],
                    'description' => 'Generic customer master category.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function defaultTenantId(): int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($id === null) {
            throw new RuntimeException('Seed a tenant before running the Customer module seeder.');
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
