<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Pricing\Domain\Exceptions\NoApplicablePriceFoundException;
use Tests\TestCase;

class PricingResolveServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ResolvePriceServiceInterface $resolvePriceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolvePriceService = app(ResolvePriceServiceInterface::class);
    }

    public function test_resolve_sales_prefers_customer_assignment_over_default_list(): void
    {
        $this->seedTenant(1001);
        $this->seedCurrency(2001, 'USD');
        $this->seedUom(3001, 1001);
        $this->seedProduct(4001, 1001, 3001);
        $this->seedCustomer(5001, 1001);

        $this->seedPriceList(6001, 1001, 2001, 'Default Sales', 'sales', true);
        $this->seedPriceList(6002, 1001, 2001, 'VIP Sales', 'sales', false);

        $this->seedPriceListItem(7001, 1001, 6001, 4001, 3001, '50.000000');
        $this->seedPriceListItem(7002, 1001, 6002, 4001, 3001, '60.000000');

        DB::table('customer_price_lists')->insert([
            'id' => 8001,
            'tenant_id' => 1001,
            'customer_id' => 5001,
            'price_list_id' => 6002,
            'priority' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resolved = $this->resolvePriceService->execute([
            'tenant_id' => 1001,
            'type' => 'sales',
            'product_id' => 4001,
            'uom_id' => 3001,
            'quantity' => '1.000000',
            'currency_id' => 2001,
            'customer_id' => 5001,
        ]);

        $this->assertSame(6002, $resolved['price_list_id']);
        $this->assertSame(7002, $resolved['price_list_item_id']);
        $this->assertSame('60.000000', $resolved['base_price']);
        $this->assertSame('60.000000', $resolved['unit_price']);
    }

    public function test_resolve_sales_prefers_variant_specific_item_when_variant_is_provided(): void
    {
        $this->seedTenant(1011);
        $this->seedCurrency(2011, 'EUR');
        $this->seedUom(3011, 1011);
        $this->seedProduct(4011, 1011, 3011);
        $this->seedVariant(5011, 1011, 4011, 'BLUE-001');

        $this->seedPriceList(6011, 1011, 2011, 'Default Sales', 'sales', true);
        $this->seedPriceListItem(7011, 1011, 6011, 4011, 3011, '10.000000', null);
        $this->seedPriceListItem(7012, 1011, 6011, 4011, 3011, '12.000000', 5011);

        $resolved = $this->resolvePriceService->execute([
            'tenant_id' => 1011,
            'type' => 'sales',
            'product_id' => 4011,
            'variant_id' => 5011,
            'uom_id' => 3011,
            'quantity' => '1.000000',
            'currency_id' => 2011,
            'customer_id' => 999999,
        ]);

        $this->assertSame(7012, $resolved['price_list_item_id']);
        $this->assertSame('12.000000', $resolved['base_price']);
    }

    public function test_resolve_sales_uses_highest_matching_min_quantity_tier(): void
    {
        $this->seedTenant(1021);
        $this->seedCurrency(2021, 'GBP');
        $this->seedUom(3021, 1021);
        $this->seedProduct(4021, 1021, 3021);

        $this->seedPriceList(6021, 1021, 2021, 'Default Sales', 'sales', true);
        $this->seedPriceListItem(7021, 1021, 6021, 4021, 3021, '10.000000', null, '1.000000');
        $this->seedPriceListItem(7022, 1021, 6021, 4021, 3021, '8.000000', null, '10.000000');

        $resolved = $this->resolvePriceService->execute([
            'tenant_id' => 1021,
            'type' => 'sales',
            'product_id' => 4021,
            'uom_id' => 3021,
            'quantity' => '12.000000',
            'currency_id' => 2021,
            'customer_id' => 999999,
        ]);

        $this->assertSame(7022, $resolved['price_list_item_id']);
        $this->assertSame('8.000000', $resolved['base_price']);
        $this->assertSame('96.000000', $resolved['total_price']);
    }

    public function test_resolve_purchase_falls_back_to_default_price_list_without_supplier_assignment(): void
    {
        $this->seedTenant(1031);
        $this->seedCurrency(2031, 'JPY');
        $this->seedUom(3031, 1031);
        $this->seedProduct(4031, 1031, 3031);

        $this->seedPriceList(6031, 1031, 2031, 'Default Purchase', 'purchase', true);
        $this->seedPriceListItem(7031, 1031, 6031, 4031, 3031, '25.000000');

        $resolved = $this->resolvePriceService->execute([
            'tenant_id' => 1031,
            'type' => 'purchase',
            'product_id' => 4031,
            'uom_id' => 3031,
            'quantity' => '2.000000',
            'currency_id' => 2031,
        ]);

        $this->assertSame(6031, $resolved['price_list_id']);
        $this->assertSame(7031, $resolved['price_list_item_id']);
        $this->assertSame('50.000000', $resolved['total_price']);
    }

    public function test_resolve_throws_when_no_applicable_price_exists(): void
    {
        $this->seedTenant(1041);
        $this->seedCurrency(2041, 'LKR');
        $this->seedUom(3041, 1041);
        $this->seedProduct(4041, 1041, 3041);

        $this->expectException(NoApplicablePriceFoundException::class);

        $this->resolvePriceService->execute([
            'tenant_id' => 1041,
            'type' => 'sales',
            'product_id' => 4041,
            'uom_id' => 3041,
            'quantity' => '1.000000',
            'currency_id' => 2041,
        ]);
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

    private function seedProduct(int $productId, int $tenantId, int $baseUomId): void
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
            'sku' => 'SKU-'.$productId,
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
        ?int $variantId = null,
        string $minQuantity = '1.000000',
    ): void {
        DB::table('price_list_items')->insert([
            'id' => $priceListItemId,
            'tenant_id' => $tenantId,
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'variant_id' => $variantId,
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
