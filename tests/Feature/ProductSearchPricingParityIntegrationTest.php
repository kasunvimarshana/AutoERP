<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;
use Tests\TestCase;

class ProductSearchPricingParityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SearchProductCatalogServiceInterface $searchProductCatalogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchProductCatalogService = app(SearchProductCatalogServiceInterface::class);
    }

    public function test_search_pricing_prefers_assigned_customer_price_list_over_default(): void
    {
        $this->seedTenant(1101);
        $this->seedCurrency(2101, 'USD');
        $this->seedUom(3101, 1101);
        $this->seedProduct(4101, 1101, 3101, 'SKU-4101');
        $this->seedCustomer(5101, 1101);

        $this->seedPriceList(6101, 1101, 2101, 'Default Sales', 'sales', true);
        $this->seedPriceList(6102, 1101, 2101, 'VIP Sales', 'sales', false);

        $this->seedPriceListItem(7101, 1101, 6101, 4101, 3101, '50.000000');
        $this->seedPriceListItem(7102, 1101, 6102, 4101, 3101, '60.000000');

        DB::table('customer_price_lists')->insert([
            'id' => 8101,
            'tenant_id' => 1101,
            'customer_id' => 5101,
            'price_list_id' => 6102,
            'priority' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->searchProductCatalogService->execute([
            'tenant_id' => 1101,
            'q' => 'SKU-4101',
            'pricing_type' => 'sales',
            'currency_id' => 2101,
            'customer_id' => 5101,
            'quantity' => '1.000000',
        ]);

        $this->assertNotEmpty($result['data']);
        $this->assertSame('60.000000', $result['data'][0]['pricing']['base_price']);
        $this->assertSame('60.000000', $result['data'][0]['pricing']['unit_price']);
        $this->assertSame('1.000000', $result['data'][0]['pricing']['quantity']);
    }

    public function test_search_pricing_uses_highest_eligible_min_quantity_tier(): void
    {
        $this->seedTenant(1111);
        $this->seedCurrency(2111, 'EUR');
        $this->seedUom(3111, 1111);
        $this->seedProduct(4111, 1111, 3111, 'SKU-4111');

        $this->seedPriceList(6111, 1111, 2111, 'Default Sales', 'sales', true);
        $this->seedPriceListItem(7111, 1111, 6111, 4111, 3111, '10.000000', '1.000000');
        $this->seedPriceListItem(7112, 1111, 6111, 4111, 3111, '8.000000', '10.000000');

        $result = $this->searchProductCatalogService->execute([
            'tenant_id' => 1111,
            'q' => 'SKU-4111',
            'pricing_type' => 'sales',
            'currency_id' => 2111,
            'quantity' => '12.000000',
        ]);

        $this->assertNotEmpty($result['data']);
        $this->assertSame('8.000000', $result['data'][0]['pricing']['base_price']);
        $this->assertSame('8.000000', $result['data'][0]['pricing']['unit_price']);
        $this->assertSame('12.000000', $result['data'][0]['pricing']['quantity']);
    }

    public function test_search_variant_row_falls_back_to_generic_product_price_when_variant_price_missing(): void
    {
        $this->seedTenant(1121);
        $this->seedCurrency(2121, 'GBP');
        $this->seedUom(3121, 1121);
        $this->seedProduct(4121, 1121, 3121, 'SKU-4121');
        $this->seedVariant(5121, 1121, 4121, 'SKU-4121-BLUE');

        $this->seedPriceList(6121, 1121, 2121, 'Default Sales', 'sales', true);
        $this->seedPriceListItem(7121, 1121, 6121, 4121, 3121, '13.000000');

        $result = $this->searchProductCatalogService->execute([
            'tenant_id' => 1121,
            'q' => 'SKU-4121-BLUE',
            'pricing_type' => 'sales',
            'currency_id' => 2121,
            'quantity' => '1.000000',
        ]);

        $this->assertNotEmpty($result['data']);
        $this->assertSame(5121, $result['data'][0]['variant_id']);
        $this->assertSame('13.000000', $result['data'][0]['pricing']['base_price']);
        $this->assertSame('13.000000', $result['data'][0]['pricing']['unit_price']);
    }

    public function test_search_purchase_prefers_supplier_assigned_list_and_quantity_tier(): void
    {
        $this->seedTenant(1131);
        $this->seedCurrency(2131, 'CAD');
        $this->seedUom(3131, 1131);
        $this->seedProduct(4131, 1131, 3131, 'SKU-4131');
        $this->seedSupplier(5131, 1131);

        $this->seedPriceList(6131, 1131, 2131, 'Default Purchase', 'purchase', true);
        $this->seedPriceList(6132, 1131, 2131, 'Vendor Contract', 'purchase', false);

        $this->seedPriceListItem(7131, 1131, 6131, 4131, 3131, '30.000000', '1.000000');
        $this->seedPriceListItem(7132, 1131, 6132, 4131, 3131, '28.000000', '1.000000');
        $this->seedPriceListItem(7133, 1131, 6132, 4131, 3131, '25.000000', '10.000000');

        DB::table('supplier_price_lists')->insert([
            'id' => 8131,
            'tenant_id' => 1131,
            'supplier_id' => 5131,
            'price_list_id' => 6132,
            'priority' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->searchProductCatalogService->execute([
            'tenant_id' => 1131,
            'q' => 'SKU-4131',
            'pricing_type' => 'purchase',
            'currency_id' => 2131,
            'supplier_id' => 5131,
            'quantity' => '12.000000',
        ]);

        $this->assertNotEmpty($result['data']);
        $this->assertSame('25.000000', $result['data'][0]['pricing']['base_price']);
        $this->assertSame('25.000000', $result['data'][0]['pricing']['unit_price']);
        $this->assertSame('12.000000', $result['data'][0]['pricing']['quantity']);
    }

    public function test_search_purchase_variant_row_falls_back_to_generic_product_price_when_variant_price_missing(): void
    {
        $this->seedTenant(1141);
        $this->seedCurrency(2141, 'AUD');
        $this->seedUom(3141, 1141);
        $this->seedProduct(4141, 1141, 3141, 'SKU-4141');
        $this->seedVariant(5141, 1141, 4141, 'SKU-4141-V1');

        $this->seedPriceList(6141, 1141, 2141, 'Default Purchase', 'purchase', true);
        $this->seedPriceListItem(7141, 1141, 6141, 4141, 3141, '19.000000');

        $result = $this->searchProductCatalogService->execute([
            'tenant_id' => 1141,
            'q' => 'SKU-4141-V1',
            'pricing_type' => 'purchase',
            'currency_id' => 2141,
            'quantity' => '1.000000',
        ]);

        $this->assertNotEmpty($result['data']);
        $this->assertSame(5141, $result['data'][0]['variant_id']);
        $this->assertSame('19.000000', $result['data'][0]['pricing']['base_price']);
        $this->assertSame('19.000000', $result['data'][0]['pricing']['unit_price']);
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

    private function seedUom(int $uomId, int $tenantId): void
    {
        DB::table('units_of_measure')->insert([
            'id' => $uomId,
            'tenant_id' => $tenantId,
            'name' => 'Each',
            'symbol' => 'EA'.$uomId,
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedProduct(int $productId, int $tenantId, int $baseUomId, string $sku): void
    {
        DB::table('products')->insert([
            'id' => $productId,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Product '.$productId,
            'slug' => 'product-'.$productId,
            'sku' => $sku,
            'description' => null,
            'image_path' => null,
            'base_uom_id' => $baseUomId,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedCustomer(int $customerId, int $tenantId): void
    {
        DB::table('customers')->insert([
            'id' => $customerId,
            'tenant_id' => $tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => 'CUS-'.$customerId,
            'name' => 'Customer '.$customerId,
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'credit_limit' => '0.000000',
            'payment_terms_days' => 30,
            'ar_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedSupplier(int $supplierId, int $tenantId): void
    {
        DB::table('suppliers')->insert([
            'id' => $supplierId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'supplier_code' => 'SUP-'.$supplierId,
            'name' => 'Supplier '.$supplierId,
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'payment_terms_days' => 30,
            'ap_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedVariant(int $variantId, int $tenantId, int $productId, string $sku): void
    {
        DB::table('product_variants')->insert([
            'id' => $variantId,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'sku' => $sku,
            'name' => 'Variant '.$variantId,
            'is_default' => false,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedPriceList(
        int $priceListId,
        int $tenantId,
        int $currencyId,
        string $name,
        string $type,
        bool $isDefault,
    ): void {
        DB::table('price_lists')->insert([
            'id' => $priceListId,
            'tenant_id' => $tenantId,
            'name' => $name,
            'type' => $type,
            'currency_id' => $currencyId,
            'is_default' => $isDefault,
            'valid_from' => null,
            'valid_to' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPriceListItem(
        int $priceListItemId,
        int $tenantId,
        int $priceListId,
        int $productId,
        int $uomId,
        string $price,
        string $minQuantity = '1.000000',
    ): void {
        DB::table('price_list_items')->insert([
            'id' => $priceListItemId,
            'tenant_id' => $tenantId,
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'variant_id' => null,
            'uom_id' => $uomId,
            'min_quantity' => $minQuantity,
            'price' => $price,
            'discount_pct' => '0.000000',
            'valid_from' => null,
            'valid_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
