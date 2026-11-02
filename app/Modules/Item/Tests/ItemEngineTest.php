<?php

declare(strict_types=1);

namespace Modules\Item\Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\DTOs\ItemBundleData;
use Modules\Item\DTOs\ItemCodeData;
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\DTOs\ItemUsageRuleData;
use Modules\Item\DTOs\ItemVariantData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemCodeType;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemBrand;
use Modules\Item\Models\ItemCategory;
use Modules\Item\Services\ItemBundleService;
use Modules\Item\Services\ItemCreationService;
use Modules\Item\Services\ItemLookupService;
use Tests\TestCase;

final class ItemEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_item_with_units_variants_prices_codes_and_usage_rules(): void
    {
        $tenantId = $this->createTenant();
        $currencyId = $this->createCurrency('LKR');
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $currencyId]);
        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'ITEM-A');
        $uomId = $this->createUom($tenantId, $organizationUnitId, 'PCS');
        $category = $this->createCategory($tenantId, $organizationUnitId, 'PARTS');
        $brand = $this->createBrand($tenantId, $organizationUnitId, 'GEN');

        $item = $this->createItem(new CreateItemData(
            tenantId: $tenantId,
            code: 'ITM-001',
            name: 'Generic Part',
            itemType: ItemType::Stock,
            organizationUnitId: $organizationUnitId,
            itemCategoryId: (int) $category->getKey(),
            itemBrandId: (int) $brand->getKey(),
            sku: 'SKU-001',
            barcode: 'BAR-001',
            trackingType: TrackingType::Serial,
            costingMethod: CostingMethod::WeightedAverage,
            baseUomId: $uomId,
            isStockable: true,
            units: [
                new ItemUnitData($uomId, ItemUnitRole::Base, isDefault: true),
            ],
            variants: [
                new ItemVariantData('ITM-001-RED', 'Red'),
            ],
            prices: [
                new ItemPriceData(
                    priceType: ItemPriceType::Sales,
                    amount: '1275.000000',
                    currencyId: $currencyId,
                    uomId: $uomId,
                    organizationUnitId: $organizationUnitId,
                    effectiveFrom: '2026-01-01',
                ),
            ],
            codes: [
                new ItemCodeData(ItemCodeType::InternalCode, 'INT-001', isPrimary: true),
            ],
            usageRules: [
                new ItemUsageRuleData('inventory'),
                new ItemUsageRuleData('purchase'),
            ],
        ));

        $this->assertSame('ITM-001', $item->code);
        $this->assertSame(ItemType::Stock, $item->item_type);
        $this->assertTrue((bool) $item->is_stockable);
        $this->assertCount(1, $item->units);
        $this->assertCount(1, $item->variants);
        $this->assertCount(1, $item->prices);
        $this->assertCount(1, $item->codes);
        $this->assertCount(2, $item->usageRules);
        $this->assertSame('1275.000000', (string) $item->prices->first()->amount);
        $this->assertSame(1, (int) $item->prices->first()->revision_no);
    }

    public function test_category_hierarchy_relationships_work(): void
    {
        $tenantId = $this->createTenant();
        $parent = $this->createCategory($tenantId, null, 'ROOT');
        $child = $this->createCategory($tenantId, null, 'CHILD', (int) $parent->getKey());

        $this->withTenantExecutionContext($tenantId, function () use ($parent, $child): void {
            $this->assertSame((int) $parent->getKey(), (int) $child->load('parent')->parent->getKey());
            $this->assertTrue($parent->load('children')->children->contains($child));
        });
    }

    public function test_bundle_creation_and_circular_bundle_prevention(): void
    {
        $tenantId = $this->createTenant();
        $uomId = $this->createUom($tenantId, null, 'PCS');
        $component = $this->createBasicItem($tenantId, 'CHILD', ItemType::Stock, true, $uomId);

        $package = $this->createItem(new CreateItemData(
            tenantId: $tenantId,
            code: 'KIT-001',
            name: 'Starter Kit',
            itemType: ItemType::Package,
            isStockable: false,
            bundles: [
                new ItemBundleData((int) $component->getKey(), '2.000000', ItemType::Stock->value, uomId: $uomId),
            ],
        ));

        $this->assertTrue((bool) $package->is_combo);
        $this->assertCount(1, $package->bundleLines);

        $nestedPackage = $this->createItem(new CreateItemData(
            tenantId: $tenantId,
            code: 'KIT-002',
            name: 'Nested Kit',
            itemType: ItemType::Package,
            isStockable: false,
            bundles: [
                new ItemBundleData((int) $component->getKey(), '1.000000', ItemType::Stock->value, uomId: $uomId),
            ],
        ));

        DB::table('item_bundles')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'parent_item_id' => $nestedPackage->getKey(),
            'child_item_id' => $package->getKey(),
            'quantity' => '1.000000',
            'line_type' => ItemType::NonStock->value,
            'is_required' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Item bundle cannot create a circular composition.');

        $this->withTenantExecutionContext($tenantId, fn () => app(ItemBundleService::class)->addLine(
            $package,
            new ItemBundleData(
                childItemId: (int) $nestedPackage->getKey(),
                quantity: '1.000000',
                lineType: ItemType::NonStock->value,
            ),
        ));
    }

    public function test_it_rejects_invalid_item_rules(): void
    {
        $tenantId = $this->createTenant();

        $this->expectException(ValidationException::class);

        $this->createItem(new CreateItemData(
            tenantId: $tenantId,
            code: 'SVC-STOCK',
            name: 'Invalid Service',
            itemType: ItemType::Service,
            isStockable: true,
        ));
    }

    public function test_duplicate_item_code_is_rejected_per_tenant(): void
    {
        $tenantId = $this->createTenant();
        $this->createBasicItem($tenantId, 'DUP', ItemType::Stock, true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item code already exists for this tenant.');

        $this->createBasicItem($tenantId, 'DUP', ItemType::Stock, true);
    }

    public function test_lookup_service_filters_item_types_and_isolates_tenants(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant('OTHER');

        $this->createBasicItem($tenantId, 'STOCK-1', ItemType::Stock, true);
        $this->createBasicItem($tenantId, 'SERVICE-1', ItemType::Service, false);
        $this->createBasicItem($tenantId, 'LABOUR-1', ItemType::Labour, false);
        $this->createBasicItem($otherTenantId, 'STOCK-OTHER', ItemType::Stock, true);

        $lookup = app(ItemLookupService::class);

        $this->withTenantExecutionContext($tenantId, function () use ($lookup, $tenantId): void {
            $this->assertCount(3, $lookup->activeItems($tenantId));
            $this->assertCount(1, $lookup->stockItems($tenantId));
            $this->assertCount(1, $lookup->serviceItems($tenantId));
            $this->assertCount(1, $lookup->labourItems($tenantId));
        });
    }

    public function test_cross_organization_uom_reference_is_rejected(): void
    {
        $tenantId = $this->createTenant();
        $orgOne = $this->createOrganizationUnit($tenantId, 'ORG-1');
        $orgTwo = $this->createOrganizationUnit($tenantId, 'ORG-2');
        $uomId = $this->createUom($tenantId, $orgOne, 'PCS');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item reference belongs to a different organization unit.');

        $this->createItem(new CreateItemData(
            tenantId: $tenantId,
            organizationUnitId: $orgTwo,
            code: 'ORG-MISMATCH',
            name: 'Org Mismatch',
            itemType: ItemType::Stock,
            baseUomId: $uomId,
            isStockable: true,
        ));
    }

    public function test_database_seeder_adds_item_master_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');

        $this->assertDatabaseHas('item_categories', [
            'tenant_id' => $tenantId,
            'code' => 'PARTS',
        ]);
        $this->assertDatabaseHas('item_brands', [
            'tenant_id' => $tenantId,
            'code' => 'GENERIC',
        ]);
        $this->assertDatabaseHas('items', [
            'tenant_id' => $tenantId,
            'code' => 'FULL-SERVICE-PACKAGE',
        ]);
        $this->assertSame(6, $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => Item::query()->where('tenant_id', $tenantId)->count(),
        ));
    }

    private function createBasicItem(
        int $tenantId,
        string $code,
        ItemType $type,
        bool $stockable,
        ?int $uomId = null,
    ): Item {
        $bundles = [];
        if (in_array($type, [ItemType::Combo, ItemType::Package], true)) {
            throw new InvalidArgumentException('Test helper does not create bundled parent items.');
        }

        return $this->createItem(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: 'Item '.$code,
            itemType: $type,
            baseUomId: $uomId,
            isStockable: $stockable,
            bundles: $bundles,
        ));
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-ITEM-'.$suffix,
            'name' => 'Item Tenant '.$suffix,
            'slug' => 'item-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);

        \Tests\Support\ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }

    private function createCurrency(string $code): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => $code.' Currency',
            'symbol' => $code,
            'decimal_places' => 2,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$code,
            'code' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUom(int $tenantId, ?int $organizationUnitId, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => 1,
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => 'Unit '.$code.' '.Str::random(4),
            'symbol' => Str::lower($code),
            'category' => 'UNIT',
            'type' => 'UNIT',
            'decimal_precision' => 0,
            'allow_fractional_quantity' => false,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCategory(int $tenantId, ?int $organizationUnitId, string $code, ?int $parentId = null): ItemCategory
    {
        return $this->withTenantExecutionContext($tenantId, fn (): ItemCategory => ItemCategory::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'parent_id' => $parentId,
            'code' => $code,
            'name' => 'Category '.$code,
            'is_active' => true,
        ]));
    }

    private function createBrand(int $tenantId, ?int $organizationUnitId, string $code): ItemBrand
    {
        return $this->withTenantExecutionContext($tenantId, fn (): ItemBrand => ItemBrand::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => 'Brand '.$code,
            'is_active' => true,
        ]));
    }

    private function createItem(CreateItemData $data): Item
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): Item => app(ItemCreationService::class)->create($data),
        );
    }
}
