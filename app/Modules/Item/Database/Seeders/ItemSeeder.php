<?php

declare(strict_types=1);

namespace Modules\Item\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBrand;
use Modules\Item\Models\ItemBundle;
use Modules\Item\Models\ItemCategory;
use Modules\Item\Models\ItemPrice;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemPriceScopeKey;
use Modules\UOM\Models\UnitOfMeasureModel;

final class ItemSeeder extends Seeder
{
    use ResolvesSeedContext;

    private const SAMPLE_PRICE_EFFECTIVE_FROM = '2026-01-01';

    public function run(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        $this->seedPermissions();

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
            'ENGINE-OIL' => ['Engine Oil', 'CONSUMABLES', 'consumable', 'LTR', true, false, 'none', 'weighted_average'],
            'OIL-FILTER' => ['Oil Filter', 'PARTS', 'stock', 'PCS', true, false, 'none', 'fifo'],
            'BRAKE-PAD' => ['Brake Pad', 'PARTS', 'stock', 'PCS', true, false, 'none', 'fifo'],
            'LABOUR-SERVICE' => ['Labour Service', 'LABOUR', 'labour', 'HOUR', false, false, 'none', 'none'],
            'INSPECTION-SERVICE' => ['Inspection Service', 'SERVICES', 'service', 'HOUR', false, false, 'none', 'none'],
            'FULL-SERVICE-PACKAGE' => ['Full Service Package', 'PACKAGES', 'package', 'PCS', false, true, 'none', 'none'],
        ];

        $items = [];
        foreach ($definitions as $code => [$name, $categoryCode, $itemType, $uomCode, $stockable, $combo, $tracking, $costing]) {
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
            if ($item->base_uom_id === null) {
                continue;
            }

            $scopeKey = ItemPriceScopeKey::for(
                organizationUnitId: $organizationUnitId,
                itemVariantId: null,
                priceType: ItemPriceType::Sales,
                currencyId: (int) $currency->getKey(),
                uomId: (int) $item->base_uom_id,
            );

            ItemPrice::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'item_id' => $item->getKey(),
                    'scope_key' => $scopeKey,
                    'effective_from' => self::SAMPLE_PRICE_EFFECTIVE_FROM,
                    'recorded_to' => null,
                ],
                [
                    'row_version' => 1,
                    'organization_unit_id' => $organizationUnitId,
                    'item_variant_id' => null,
                    'price_type' => ItemPriceType::Sales->value,
                    'currency_id' => $currency->getKey(),
                    'uom_id' => $item->base_uom_id,
                    'amount' => $amount,
                    'effective_to' => null,
                    'lineage_key' => (string) Str::uuid(),
                    'revision_no' => 1,
                    'supersedes_price_id' => null,
                    'recorded_from' => now(),
                    'correction_reason' => null,
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

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $guard = (string) config('auth.defaults.guard', 'web');
        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (ItemAuthorizationService::descriptions() as $name => $description) {
                DB::table('permissions')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard],
                    [
                        'module' => 'Item',
                        'description' => $description,
                        'row_version' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }
}
