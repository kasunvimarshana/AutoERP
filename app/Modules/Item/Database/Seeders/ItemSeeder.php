<?php

declare(strict_types=1);

namespace Modules\Item\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBrand;
use Modules\Item\Models\ItemBundle;
use Modules\Item\Models\ItemCategory;
use Modules\Item\Models\ItemPrice;
use Modules\Item\Models\ItemUnit;
use Modules\UOM\Models\UnitOfMeasureModel;

final class ItemSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('items')) {
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
            $categories = $this->seedCategories($tenantId, $organizationUnitId);
            $brand = $this->seedBrand($tenantId, $organizationUnitId);
            $items = $this->seedItems($tenantId, $organizationUnitId, $categories, $brand);
            $this->seedUnits($tenantId, $organizationUnitId, $items);
            $this->seedPrices($tenantId, $organizationUnitId, $items);
            $this->seedPackage($tenantId, $organizationUnitId, $items);
        }, 3);
    }

    /**
     * @return array<string,ItemCategory>
     */
    private function seedCategories(int $tenantId, ?int $organizationUnitId): array
    {
        $definitions = [
            'PARTS' => 'Parts',
            'SERVICES' => 'Services',
            'LABOUR' => 'Labour',
            'CONSUMABLES' => 'Consumables',
            'PACKAGES' => 'Packages',
        ];

        $categories = [];
        foreach ($definitions as $code => $name) {
            $categories[$code] = ItemCategory::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'parent_id' => null,
                    'name' => $name,
                    'description' => 'Default AutoERP item category.',
                    'is_active' => true,
                    'sort_order' => count($categories) + 1,
                ],
            );
        }

        return $categories;
    }

    private function seedBrand(int $tenantId, ?int $organizationUnitId): ItemBrand
    {
        return ItemBrand::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'GENERIC'],
            [
                'organization_unit_id' => $organizationUnitId,
                'name' => 'Generic',
                'description' => 'Generic item brand.',
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string,ItemCategory>  $categories
     * @return array<string,Item>
     */
    private function seedItems(
        int $tenantId,
        ?int $organizationUnitId,
        array $categories,
        ItemBrand $brand,
    ): array {
        $definitions = [
            'ENGINE-OIL' => ['Engine Oil', 'CONSUMABLES', 'consumable', 'LTR', true, false, 'none', 'weighted_average', '20.000000'],
            'OIL-FILTER' => ['Oil Filter', 'PARTS', 'stock', 'PCS', true, false, 'none', 'fifo', '10.000000'],
            'BRAKE-PAD' => ['Brake Pad', 'PARTS', 'stock', 'PCS', true, false, 'none', 'fifo', '50.000000'],
            'LABOUR-SERVICE' => ['Labour Service', 'LABOUR', 'labour', 'HOUR', false, false, 'none', 'none', '25.000000'],
            'INSPECTION-SERVICE' => ['Inspection Service', 'SERVICES', 'service', 'HOUR', false, false, 'none', 'none', '15.000000'],
            'FULL-SERVICE-PACKAGE' => ['Full Service Package', 'PACKAGES', 'package', 'PCS', false, true, 'none', 'none', '100.000000'],
        ];

        $items = [];
        foreach ($definitions as $code => [$name, $categoryCode, $itemType, $uomCode, $stockable, $combo, $tracking, $costing, $standardPrice]) {
            $uom = $this->uom($tenantId, $uomCode);
            $items[$code] = Item::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'item_category_id' => $categories[$categoryCode]->getKey(),
                    'item_brand_id' => $brand->getKey(),
                    'sku' => $code,
                    'barcode' => null,
                    'name' => $name,
                    'description' => 'Default AutoERP sample item.',
                    'item_type' => $itemType,
                    'tracking_type' => $tracking,
                    'costing_method' => $costing,
                    'base_uom_id' => $uom?->getKey(),
                    'standard_price' => $standardPrice,
                    'is_stockable' => $stockable,
                    'is_combo' => $combo,
                    'is_active' => true,
                    'metadata' => ['seed_source' => 'item_module'],
                ],
            );
        }

        return $items;
    }

    /**
     * @param  array<string,Item>  $items
     */
    private function seedUnits(int $tenantId, ?int $organizationUnitId, array $items): void
    {
        if (! Schema::hasTable('item_units')) {
            return;
        }

        $roles = [
            'ENGINE-OIL' => ['LTR', ['base', 'purchase', 'sales']],
            'OIL-FILTER' => ['PCS', ['base', 'purchase', 'sales']],
            'BRAKE-PAD' => ['PCS', ['base', 'purchase', 'sales']],
            'LABOUR-SERVICE' => ['HOUR', ['base', 'service', 'sales']],
            'INSPECTION-SERVICE' => ['HOUR', ['base', 'service', 'sales']],
            'FULL-SERVICE-PACKAGE' => ['PCS', ['base', 'service', 'sales']],
        ];

        foreach ($roles as $itemCode => [$uomCode, $unitRoles]) {
            $uom = $this->uom($tenantId, $uomCode);
            if ($uom === null) {
                continue;
            }

            foreach ($unitRoles as $role) {
                ItemUnit::query()->updateOrCreate(
                    [
                        'item_id' => $items[$itemCode]->getKey(),
                        'uom_id' => $uom->getKey(),
                        'unit_role' => $role,
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'conversion_factor' => '1.000000',
                        'is_default' => $role === 'base',
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string,Item>  $items
     */
    private function seedPrices(int $tenantId, ?int $organizationUnitId, array $items): void
    {
        $currency = $this->defaultCurrency();
        if ($currency === null || ! Schema::hasTable('item_prices')) {
            return;
        }

        $prices = [
            'ENGINE-OIL' => '20.000000',
            'OIL-FILTER' => '10.000000',
            'BRAKE-PAD' => '50.000000',
            'LABOUR-SERVICE' => '25.000000',
            'INSPECTION-SERVICE' => '15.000000',
            'FULL-SERVICE-PACKAGE' => '100.000000',
        ];

        foreach ($prices as $itemCode => $amount) {
            $item = $items[$itemCode];
            ItemPrice::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'item_id' => $item->getKey(),
                    'price_type' => 'sales',
                    'currency_id' => $currency->getKey(),
                    'uom_id' => $item->base_uom_id,
                ],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'item_variant_id' => null,
                    'amount' => $amount,
                    'effective_from' => null,
                    'effective_to' => null,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string,Item>  $items
     */
    private function seedPackage(int $tenantId, ?int $organizationUnitId, array $items): void
    {
        if (! Schema::hasTable('item_bundles')) {
            return;
        }

        $components = [
            'ENGINE-OIL' => ['1.000000', 'stock'],
            'OIL-FILTER' => ['1.000000', 'stock'],
            'LABOUR-SERVICE' => ['1.000000', 'labour'],
            'INSPECTION-SERVICE' => ['1.000000', 'service'],
        ];

        foreach ($components as $code => [$quantity, $lineType]) {
            $child = $items[$code];
            ItemBundle::query()->updateOrCreate(
                [
                    'parent_item_id' => $items['FULL-SERVICE-PACKAGE']->getKey(),
                    'child_item_id' => $child->getKey(),
                    'line_type' => $lineType,
                ],
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'child_variant_id' => null,
                    'quantity' => $quantity,
                    'uom_id' => $child->base_uom_id,
                    'is_required' => true,
                    'sort_order' => array_search($code, array_keys($components), true) + 1,
                ],
            );
        }
    }

    private function uom(int $tenantId, string $code): ?UnitOfMeasureModel
    {
        if (! Schema::hasTable('unit_of_measures')) {
            return null;
        }

        return UnitOfMeasureModel::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

}
