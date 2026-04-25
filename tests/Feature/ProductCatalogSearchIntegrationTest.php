<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Tests\TestCase;

class ProductCatalogSearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SearchProductCatalogServiceInterface $searchProductCatalogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchProductCatalogService = app(SearchProductCatalogServiceInterface::class);
    }

    public function test_search_finds_product_by_identifier_and_returns_stock_pricing_and_relationships(): void
    {
        $tenantId = 901;
        $this->seedTenant($tenantId);
        $this->seedCurrency(991, 'USD');
        $this->seedCatalogReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Main Store',
            'code' => 'MS-01',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Shelf A',
            'code' => 'A1',
            'type' => 'shelf',
        ]);

        $this->seedProducts($tenantId);
        $this->seedVariants($tenantId);
        $this->seedIdentifiers($tenantId);
        $this->seedBatchAndSerial($tenantId);
        $this->seedVariantAttributes($tenantId);
        $this->seedStock($tenantId, $location->getId());
        $this->seedPricing($tenantId, 991);
        $this->seedComboRelationship($tenantId);

        $payload = $this->searchProductCatalogService->execute([
            'tenant_id' => $tenantId,
            'term' => 'QR-ALPHA-001',
            'workflow_context' => 'pos',
            'pricing_type' => 'sales',
            'currency_id' => 991,
            'warehouse_id' => $warehouse->getId(),
            'stock_status' => 'in_stock',
            'quantity' => '2.000000',
            'per_page' => 10,
            'page' => 1,
        ]);

        $this->assertSame(1, $payload['meta']['total']);
        $this->assertCount(1, $payload['data']);

        $row = $payload['data'][0];

        $this->assertSame('Alpha Phone', $row['name']);
        $this->assertSame('ALPHA-BLK-128', $row['sku']);
        $this->assertSame('in_stock', $row['stock_status']);
        $this->assertSame('25.000000', $row['available_quantity']);
        $this->assertNotNull($row['pricing']);
        $this->assertSame('2.000000', $row['pricing']['quantity']);
        $this->assertGreaterThan(0, (float) $row['pricing']['unit_price']);
        $this->assertGreaterThan(0, (float) $row['pricing']['total_price']);
        $this->assertNotEmpty($row['identifiers']);
        $this->assertNotEmpty($row['variant_attributes']);
        $this->assertNotEmpty($row['relationships']['combo_components']);
    }

    public function test_search_matches_lot_number_and_filters_low_stock(): void
    {
        $tenantId = 902;
        $this->seedTenant($tenantId);
        $this->seedCurrency(992, 'EUR');
        $this->seedCatalogReferenceData($tenantId);

        /** @var CreateWarehouseServiceInterface $createWarehouseService */
        $createWarehouseService = app(CreateWarehouseServiceInterface::class);
        /** @var CreateWarehouseLocationServiceInterface $createWarehouseLocationService */
        $createWarehouseLocationService = app(CreateWarehouseLocationServiceInterface::class);

        $warehouse = $createWarehouseService->execute([
            'tenant_id' => $tenantId,
            'name' => 'Distribution',
            'code' => 'DC-01',
            'is_default' => true,
        ]);

        $location = $createWarehouseLocationService->execute([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouse->getId(),
            'name' => 'Rack B',
            'code' => 'B1',
            'type' => 'rack',
        ]);

        $this->seedProducts($tenantId);
        $this->seedVariants($tenantId);
        $this->seedBatchAndSerial($tenantId);
        $this->seedStock($tenantId, $location->getId(), '3.000000');

        $payload = $this->searchProductCatalogService->execute([
            'tenant_id' => $tenantId,
            'term' => 'LOT-ALPHA-001',
            'warehouse_id' => $warehouse->getId(),
            'stock_status' => 'low_stock',
            'low_stock_threshold' => '5.000000',
            'include_pricing' => false,
        ]);

        $this->assertSame(1, $payload['meta']['total']);
        $this->assertSame('low_stock', $payload['data'][0]['stock_status']);
        $this->assertSame('3.000000', $payload['data'][0]['available_quantity']);
        $this->assertNull($payload['data'][0]['pricing']);
    }

    private function seedTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedCurrency(int $currencyId, string $code): void
    {
        DB::table('currencies')->insert([
            'id' => $currencyId,
            'code' => $code,
            'name' => $code,
            'symbol' => $code,
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedCatalogReferenceData(int $tenantId): void
    {
        DB::table('units_of_measure')->insert([
            [
                'id' => 8101,
                'tenant_id' => $tenantId,
                'name' => 'Each',
                'symbol' => 'EA',
                'type' => 'unit',
                'is_base' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 8102,
                'tenant_id' => $tenantId,
                'name' => 'Box',
                'symbol' => 'BOX',
                'type' => 'unit',
                'is_base' => false,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        DB::table('product_categories')->insert([
            'id' => 8201,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'code' => 'ELEC',
            'path' => '/electronics',
            'image_path' => null,
            'depth' => 0,
            'is_active' => true,
            'description' => null,
            'attributes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('product_brands')->insert([
            'id' => 8301,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => 'Contoso',
            'slug' => 'contoso',
            'code' => 'CONT',
            'path' => '/contoso',
            'image_path' => null,
            'depth' => 0,
            'is_active' => true,
            'website' => null,
            'description' => null,
            'attributes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedProducts(int $tenantId): void
    {
        DB::table('products')->insert([
            [
                'id' => 8401,
                'tenant_id' => $tenantId,
                'category_id' => 8201,
                'brand_id' => 8301,
                'org_unit_id' => null,
                'type' => 'physical',
                'name' => 'Alpha Phone',
                'slug' => 'alpha-phone',
                'sku' => 'ALPHA',
                'description' => null,
                'image_path' => null,
                'base_uom_id' => 8101,
                'purchase_uom_id' => 8102,
                'sales_uom_id' => 8101,
                'tax_group_id' => null,
                'uom_conversion_factor' => '1.0000000000',
                'is_batch_tracked' => true,
                'is_lot_tracked' => true,
                'is_serial_tracked' => true,
                'valuation_method' => 'fifo',
                'standard_cost' => '9.500000',
                'income_account_id' => null,
                'cogs_account_id' => null,
                'inventory_account_id' => null,
                'expense_account_id' => null,
                'is_active' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 8402,
                'tenant_id' => $tenantId,
                'category_id' => 8201,
                'brand_id' => 8301,
                'org_unit_id' => null,
                'type' => 'physical',
                'name' => 'Beta Phone',
                'slug' => 'beta-phone',
                'sku' => 'BETA',
                'description' => null,
                'image_path' => null,
                'base_uom_id' => 8101,
                'purchase_uom_id' => 8102,
                'sales_uom_id' => 8101,
                'tax_group_id' => null,
                'uom_conversion_factor' => '1.0000000000',
                'is_batch_tracked' => false,
                'is_lot_tracked' => false,
                'is_serial_tracked' => false,
                'valuation_method' => 'fifo',
                'standard_cost' => '8.500000',
                'income_account_id' => null,
                'cogs_account_id' => null,
                'inventory_account_id' => null,
                'expense_account_id' => null,
                'is_active' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }

    private function seedVariants(int $tenantId): void
    {
        DB::table('product_variants')->insert([
            'id' => 8501,
            'tenant_id' => $tenantId,
            'product_id' => 8401,
            'sku' => 'ALPHA-BLK-128',
            'name' => 'Black / 128GB',
            'is_default' => true,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedIdentifiers(int $tenantId): void
    {
        DB::table('product_identifiers')->insert([
            [
                'id' => 8601,
                'tenant_id' => $tenantId,
                'product_id' => 8401,
                'variant_id' => 8501,
                'batch_id' => null,
                'serial_id' => null,
                'technology' => 'qr_code',
                'format' => 'qr',
                'value' => 'QR-ALPHA-001',
                'gs1_company_prefix' => null,
                'gs1_application_identifiers' => null,
                'is_primary' => true,
                'is_active' => true,
                'format_config' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 8602,
                'tenant_id' => $tenantId,
                'product_id' => 8401,
                'variant_id' => 8501,
                'batch_id' => null,
                'serial_id' => null,
                'technology' => 'rfid_uhf',
                'format' => 'other',
                'value' => 'RFID-ALPHA-001',
                'gs1_company_prefix' => null,
                'gs1_application_identifiers' => null,
                'is_primary' => false,
                'is_active' => true,
                'format_config' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }

    private function seedBatchAndSerial(int $tenantId): void
    {
        DB::table('batches')->insert([
            'id' => 8701,
            'tenant_id' => $tenantId,
            'product_id' => 8401,
            'variant_id' => 8501,
            'batch_number' => 'BATCH-ALPHA-001',
            'lot_number' => 'LOT-ALPHA-001',
            'manufacture_date' => null,
            'expiry_date' => null,
            'received_date' => null,
            'supplier_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'sales_price' => '15.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('serials')->insert([
            'id' => 8801,
            'tenant_id' => $tenantId,
            'product_id' => 8401,
            'variant_id' => 8501,
            'serial_number' => 'SER-ALPHA-001',
            'batch_id' => 8701,
            'status' => 'available',
            'current_location_id' => null,
            'current_owner_type' => null,
            'current_owner_id' => null,
            'warranty_expiry' => null,
            'notes' => null,
            'sales_price' => '15.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedVariantAttributes(int $tenantId): void
    {
        DB::table('attribute_groups')->insert([
            'id' => 8901,
            'tenant_id' => $tenantId,
            'name' => 'Display',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('attributes')->insert([
            'id' => 8902,
            'tenant_id' => $tenantId,
            'group_id' => 8901,
            'name' => 'Color',
            'type' => 'select',
            'is_required' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('attribute_values')->insert([
            'id' => 8903,
            'tenant_id' => $tenantId,
            'attribute_id' => 8902,
            'value' => 'Black',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('variant_attribute_values')->insert([
            'tenant_id' => $tenantId,
            'variant_id' => 8501,
            'attribute_value_id' => 8903,
        ]);
    }

    private function seedStock(int $tenantId, int $locationId, string $available = '25.000000'): void
    {
        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'product_id' => 8401,
            'variant_id' => 8501,
            'location_id' => $locationId,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 8101,
            'quantity_on_hand' => $available,
            'quantity_reserved' => '0.000000',
            'unit_cost' => '9.500000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPricing(int $tenantId, int $currencyId): void
    {
        DB::table('price_lists')->insert([
            'id' => 9001,
            'tenant_id' => $tenantId,
            'name' => 'POS Default',
            'type' => 'sales',
            'currency_id' => $currencyId,
            'is_default' => true,
            'valid_from' => null,
            'valid_to' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('price_list_items')->insert([
            'id' => 9002,
            'tenant_id' => $tenantId,
            'price_list_id' => 9001,
            'product_id' => 8401,
            'variant_id' => 8501,
            'uom_id' => 8101,
            'min_quantity' => '1.000000',
            'price' => '12.000000',
            'discount_pct' => '0.000000',
            'valid_from' => null,
            'valid_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedComboRelationship(int $tenantId): void
    {
        DB::table('combo_items')->insert([
            'id' => 9101,
            'tenant_id' => $tenantId,
            'combo_product_id' => 8401,
            'component_product_id' => 8402,
            'component_variant_id' => null,
            'quantity' => '1.000000',
            'uom_id' => 8101,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
