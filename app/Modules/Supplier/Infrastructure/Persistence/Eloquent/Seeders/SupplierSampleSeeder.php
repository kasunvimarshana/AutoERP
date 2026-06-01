<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SupplierSampleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('suppliers') || ! Schema::hasTable('tenants')) {
            return;
        }

        $tenant = DB::table('tenants')->where('is_active', true)->orderBy('id')->first();
        if ($tenant === null) {
            return;
        }

        $tenantId = (int) $tenant->id;
        $organizationUnitId = DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        DB::transaction(function () use ($tenantId, $organizationUnitId): void {
            $categoryId = $this->ensureCategory($tenantId, $organizationUnitId);
            $now = now();

            DB::table('suppliers')->updateOrInsert(
                ['tenant_id' => $tenantId, 'supplier_code' => 'SUP-GENERAL-001'],
                [
                    'row_version' => 1,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => json_encode(['seed_source' => 'supplier_sample']),
                    'supplier_name' => 'General Parts Supplier',
                    'legal_name' => 'General Parts Supplier',
                    'display_name' => 'General Parts Supplier',
                    'supplier_type' => 'business',
                    'category_id' => $categoryId,
                    'registration_number' => null,
                    'tax_number' => null,
                    'vat_number' => null,
                    'email' => 'procurement@example.test',
                    'phone' => '+94 11 000 0000',
                    'mobile' => null,
                    'website' => null,
                    'default_currency_id' => null,
                    'default_payment_term_id' => null,
                    'default_payable_account_id' => null,
                    'default_expense_account_id' => null,
                    'credit_limit' => null,
                    'status' => 'active',
                    'is_active' => true,
                    'notes' => 'Default supplier for purchase workflow testing.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $supplierId = (int) DB::table('suppliers')
                ->where('tenant_id', $tenantId)
                ->where('supplier_code', 'SUP-GENERAL-001')
                ->value('id');

            $this->ensureSupplierItem($tenantId, $organizationUnitId, $supplierId);
        });
    }

    private function ensureCategory(int $tenantId, mixed $organizationUnitId): ?int
    {
        if (! Schema::hasTable('supplier_categories')) {
            return null;
        }

        DB::table('supplier_categories')->updateOrInsert(
            ['tenant_id' => $tenantId, 'code' => 'GENERAL'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'supplier_sample']),
                'name' => 'General Suppliers',
                'description' => 'Default supplier category for purchase workflow testing.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('supplier_categories')
            ->where('tenant_id', $tenantId)
            ->where('code', 'GENERAL')
            ->value('id');
    }

    private function ensureSupplierItem(int $tenantId, mixed $organizationUnitId, int $supplierId): void
    {
        if ($supplierId < 1 || ! Schema::hasTable('supplier_items') || ! Schema::hasTable('items')) {
            return;
        }

        $itemId = DB::table('items')
            ->where('tenant_id', $tenantId)
            ->where('sku', 'ITM-SHOPSUPPLY-001')
            ->value('id');

        if ($itemId === null) {
            return;
        }

        DB::table('supplier_items')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'supplier_id' => $supplierId,
                'item_id' => (int) $itemId,
                'variant_id' => null,
            ],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'supplier_sample']),
                'supplier_sku' => 'GPS-SHOPSUPPLY',
                'lead_time_days' => 3,
                'min_order_qty' => 1,
                'is_preferred' => true,
                'last_observed_unit_cost' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
