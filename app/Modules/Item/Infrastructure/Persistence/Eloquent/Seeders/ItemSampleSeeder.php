<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ItemSampleSeeder extends Seeder
{
    public function run(): void
    {
        if (
            ! Schema::hasTable('items')
            || ! Schema::hasTable('item_brands')
            || ! Schema::hasTable('item_categories')
            || ! Schema::hasTable('item_types')
            || ! Schema::hasTable('unit_of_measures')
        ) {
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

        $eachUomId = $this->uomId($tenantId, 'Each');
        if ($eachUomId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId, $organizationUnitId, $eachUomId): void {
            $now = now();
            $hourUomId = $this->uomId($tenantId, 'Hour') ?? $eachUomId;
            $dayUomId = $this->uomId($tenantId, 'Day') ?? $eachUomId;
            $boxUomId = $this->uomId($tenantId, 'Box');
            $packUomId = $this->uomId($tenantId, 'Pack');
            $monthUomId = $this->uomId($tenantId, 'Month');

            $generalCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'GENERAL', 'General');
            $partsCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'SPARE_PARTS', 'Spare Parts');
            $servicesCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'SERVICES', 'Services');
            $rentalCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'RENTAL_CHARGES', 'Rental Charges');

            $brandId = $this->ensureBrand($tenantId, $organizationUnitId, 'GENERIC', 'Generic');

            $items = [
                [
                    'sku' => 'ITM-FILTER-001',
                    'name' => 'Oil Filter',
                    'type' => 'inventory_product',
                    'category_id' => $partsCategoryId,
                    'brand_id' => $brandId,
                    'item_type_id' => $this->itemTypeId('INVENTORY_PRODUCT'),
                    'base_uom_id' => $eachUomId,
                    'default_receipt_uom_id' => $eachUomId,
                    'default_issue_uom_id' => $eachUomId,
                    'default_consumption_uom_id' => $eachUomId,
                    'description' => 'Sample stock-tracked inventory product.',
                    'is_stockable' => true,
                    'is_purchasable' => true,
                    'is_sellable' => true,
                ],
                [
                    'sku' => 'ITM-SERVICE-001',
                    'name' => 'General Inspection Service',
                    'type' => 'service',
                    'category_id' => $servicesCategoryId,
                    'brand_id' => null,
                    'item_type_id' => $this->itemTypeId('SERVICE'),
                    'base_uom_id' => $hourUomId,
                    'default_charge_uom_id' => $hourUomId,
                    'default_consumption_uom_id' => $hourUomId,
                    'description' => 'Sample service item with reusable service setup.',
                    'is_service' => true,
                    'is_chargeable' => true,
                    'is_purchasable' => false,
                    'is_sellable' => true,
                ],
                [
                    'sku' => 'ITM-LABOUR-001',
                    'name' => 'Technician Labour Hour',
                    'type' => 'labour',
                    'category_id' => $servicesCategoryId,
                    'brand_id' => null,
                    'item_type_id' => $this->itemTypeId('LABOUR'),
                    'base_uom_id' => $hourUomId,
                    'default_charge_uom_id' => $hourUomId,
                    'default_consumption_uom_id' => $hourUomId,
                    'description' => 'Sample labour item.',
                    'is_service' => true,
                    'is_chargeable' => true,
                    'is_purchasable' => false,
                    'is_sellable' => true,
                ],
                [
                    'sku' => 'ITM-SHOPSUPPLY-001',
                    'name' => 'Workshop Consumable Reference',
                    'type' => 'non_inventory',
                    'category_id' => $generalCategoryId,
                    'brand_id' => null,
                    'item_type_id' => $this->itemTypeId('NON_INVENTORY'),
                    'base_uom_id' => $eachUomId,
                    'default_receipt_uom_id' => $eachUomId,
                    'default_issue_uom_id' => $eachUomId,
                    'description' => 'Sample non-inventory item.',
                    'is_stockable' => false,
                    'is_chargeable' => true,
                ],
                [
                    'sku' => 'ITM-BUNDLE-001',
                    'name' => 'Basic Service Bundle',
                    'type' => 'combo',
                    'category_id' => $servicesCategoryId,
                    'brand_id' => null,
                    'item_type_id' => $this->itemTypeId('COMBO'),
                    'base_uom_id' => $eachUomId,
                    'default_charge_uom_id' => $eachUomId,
                    'description' => 'Sample combo item with persisted component setup.',
                    'is_chargeable' => true,
                ],
                [
                    'sku' => 'ITM-RENTAL-DAY-001',
                    'name' => 'Vehicle Rental Daily Charge',
                    'type' => 'rental_charge',
                    'category_id' => $rentalCategoryId,
                    'brand_id' => null,
                    'item_type_id' => $this->itemTypeId('RENTAL_CHARGE'),
                    'base_uom_id' => $dayUomId,
                    'default_charge_uom_id' => $dayUomId,
                    'description' => 'Sample rental charge item with reusable charge setup.',
                    'is_rentable' => true,
                    'is_chargeable' => true,
                    'is_stockable' => false,
                ],
            ];

            foreach ($items as $item) {
                DB::table('items')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'sku' => $item['sku']],
                    array_merge([
                        'row_version' => 1,
                        'organization_unit_id' => $organizationUnitId,
                        'metadata' => json_encode(['seed_source' => 'item_sample']),
                        'barcode' => null,
                        'image_path' => null,
                        'status' => 'ACTIVE',
                        'default_receipt_uom_id' => null,
                        'default_issue_uom_id' => null,
                        'default_consumption_uom_id' => null,
                        'default_charge_uom_id' => null,
                        'tax_group_id' => null,
                        'is_batch_tracked' => false,
                        'is_lot_tracked' => false,
                        'is_serial_tracked' => false,
                        'is_purchasable' => true,
                        'is_sellable' => true,
                        'is_service' => false,
                        'is_rentable' => false,
                        'is_chargeable' => false,
                        'is_taxable' => false,
                        'is_variable' => false,
                        'valuation_method' => null,
                        'allocation_method' => null,
                        'standard_cost' => null,
                        'income_account_id' => null,
                        'cogs_account_id' => null,
                        'inventory_account_id' => null,
                        'expense_account_id' => null,
                        'return_in_account_id' => null,
                        'return_out_account_id' => null,
                        'inventory_gain_account_id' => null,
                        'inventory_loss_account_id' => null,
                        'stock_transfer_account_id' => null,
                        'wip_account_id' => null,
                        'price_variance_account_id' => null,
                        'is_active' => true,
                        'default_currency_id' => null,
                        'minimum_stock' => 0,
                        'maximum_stock' => null,
                        'reorder_point' => 0,
                        'reorder_quantity' => null,
                        'safety_stock' => 0,
                        'lead_time_days' => 0,
                        'review_period_days' => 30,
                        'auto_replenishment_enabled' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $item),
                );
            }

            $comboItemId = $this->itemId($tenantId, 'ITM-BUNDLE-001');
            $filterItemId = $this->itemId($tenantId, 'ITM-FILTER-001');
            $rentalChargeItemId = $this->itemId($tenantId, 'ITM-RENTAL-DAY-001');

            if ($filterItemId !== null) {
                if ($boxUomId !== null) {
                    $this->ensureItemConversion($tenantId, $organizationUnitId, $filterItemId, $boxUomId, $eachUomId, '12', 'UNIT');
                }

                if ($packUomId !== null) {
                    $this->ensureItemConversion($tenantId, $organizationUnitId, $filterItemId, $packUomId, $eachUomId, '6', 'UNIT');
                }
            }

            if ($rentalChargeItemId !== null && $monthUomId !== null) {
                $this->ensureItemConversion($tenantId, $organizationUnitId, $rentalChargeItemId, $monthUomId, $dayUomId, '30', 'TIME');
            }

            if ($comboItemId !== null && $filterItemId !== null && Schema::hasTable('combo_items')) {
                DB::table('combo_items')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'combo_item_id' => $comboItemId,
                        'component_item_id' => $filterItemId,
                    ],
                    [
                        'row_version' => 1,
                        'organization_unit_id' => $organizationUnitId,
                        'metadata' => json_encode(['seed_source' => 'item_sample']),
                        'component_variant_id' => null,
                        'sort_order' => 1,
                        'quantity' => 1,
                        'uom_id' => $eachUomId,
                        'standard_cost' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            if ($filterItemId !== null && Schema::hasTable('item_identifiers')) {
                DB::table('item_identifiers')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'value' => '890000000001'],
                    [
                        'row_version' => 1,
                        'organization_unit_id' => $organizationUnitId,
                        'metadata' => json_encode(['seed_source' => 'item_sample']),
                        'item_id' => $filterItemId,
                        'variant_id' => null,
                        'batch_id' => null,
                        'serial_id' => null,
                        'technology' => 'barcode_1d',
                        'format' => 'code128',
                        'gs1_company_prefix' => null,
                        'gs1_application_identifiers' => null,
                        'is_primary' => true,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            if ($filterItemId !== null && Schema::hasTable('item_attributes') && Schema::hasTable('item_variants')) {
                $groupId = $this->ensureAttributeGroup($tenantId, $organizationUnitId, 'Fitment');
                $attributeId = $this->ensureAttribute($tenantId, $organizationUnitId, $groupId, 'Size', 'SELECT');
                $valueId = $this->ensureAttributeValue($tenantId, $organizationUnitId, $attributeId, 'Standard');

                DB::table('item_variant_attributes')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'item_id' => $filterItemId, 'attribute_id' => $attributeId],
                    [
                        'row_version' => 1,
                        'organization_unit_id' => $organizationUnitId,
                        'metadata' => json_encode(['seed_source' => 'item_sample']),
                        'is_required' => false,
                        'is_variation_axis' => true,
                        'display_order' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                DB::table('item_variants')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'item_id' => $filterItemId, 'name' => 'Standard'],
                    [
                        'row_version' => 1,
                        'organization_unit_id' => $organizationUnitId,
                        'metadata' => json_encode(['seed_source' => 'item_sample']),
                        'sku' => 'ITM-FILTER-001-STD',
                        'is_default' => true,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $variantId = (int) DB::table('item_variants')
                    ->where('tenant_id', $tenantId)
                    ->where('item_id', $filterItemId)
                    ->where('name', 'Standard')
                    ->value('id');

                DB::table('item_variant_attribute_values')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'variant_id' => $variantId, 'attribute_value_id' => $valueId],
                    [
                        'row_version' => 1,
                        'organization_unit_id' => $organizationUnitId,
                        'metadata' => json_encode(['seed_source' => 'item_sample']),
                        'created_by' => null,
                        'updated_by' => null,
                        'deleted_by' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        });
    }

    private function ensureCategory(int $tenantId, ?int $organizationUnitId, string $code, string $name): int
    {
        DB::table('item_categories')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'item_sample']),
                'parent_id' => null,
                'code' => $code,
                'depth' => 0,
                'path' => '/' . strtolower($code),
                'image_path' => null,
                'is_active' => true,
                'description' => 'Sample item category.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('item_categories')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->value('id');
    }

    private function ensureBrand(int $tenantId, ?int $organizationUnitId, string $code, string $name): int
    {
        DB::table('item_brands')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'item_sample']),
                'parent_id' => null,
                'slug' => strtolower($code),
                'code' => $code,
                'path' => '/' . strtolower($code),
                'image_path' => null,
                'depth' => 0,
                'is_active' => true,
                'website' => null,
                'description' => 'Sample item brand.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('item_brands')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->value('id');
    }

    private function ensureItemConversion(
        int $tenantId,
        ?int $organizationUnitId,
        int $itemId,
        int $fromUomId,
        int $toUomId,
        string $factor,
        string $category,
    ): void {
        if (! Schema::hasTable('uom_conversions')) {
            return;
        }

        DB::table('uom_conversions')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'from_uom_id' => $fromUomId,
                'to_uom_id' => $toUomId,
            ],
            [
                'category' => $category,
                'effective_from' => null,
                'effective_to' => null,
                'factor' => $factor,
                'is_active' => true,
                'is_bidirectional' => true,
                'metadata' => json_encode(['seed_source' => 'item_sample']),
                'notes' => 'Default item-specific UOM setup for real UI testing.',
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function uomId(int $tenantId, string $name): ?int
    {
        $id = DB::table('unit_of_measures')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function itemTypeId(string $code): ?int
    {
        $id = DB::table('item_types')
            ->whereNull('tenant_id')
            ->where('code', $code)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function itemId(int $tenantId, string $sku): ?int
    {
        $id = DB::table('items')
            ->where('tenant_id', $tenantId)
            ->where('sku', $sku)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function ensureAttributeGroup(int $tenantId, ?int $organizationUnitId, string $name): int
    {
        DB::table('item_attribute_groups')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'item_sample']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('item_attribute_groups')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->value('id');
    }

    private function ensureAttribute(int $tenantId, ?int $organizationUnitId, int $groupId, string $name, string $type): int
    {
        DB::table('item_attributes')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'item_sample']),
                'group_id' => $groupId,
                'type' => $type,
                'is_required' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('item_attributes')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->value('id');
    }

    private function ensureAttributeValue(int $tenantId, ?int $organizationUnitId, int $attributeId, string $value): int
    {
        DB::table('item_attribute_values')->updateOrInsert(
            ['tenant_id' => $tenantId, 'attribute_id' => $attributeId, 'value' => $value],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'item_sample']),
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('item_attribute_values')
            ->where('tenant_id', $tenantId)
            ->where('attribute_id', $attributeId)
            ->where('value', $value)
            ->value('id');
    }
}
