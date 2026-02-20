<?php

namespace Tests\Unit;

use App\Enums\PricingType;
use App\Models\PriceList;
use App\Models\PriceRule;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private PricingEngine $engine;

    private Tenant $tenant;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = new PricingEngine;

        $this->tenant = Tenant::factory()->create();

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'base_price' => '100.00000000',
            'currency' => 'USD',
        ]);
    }

    public function test_returns_base_price_when_no_rule_applies(): void
    {
        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        $this->assertSame('100.00000000', $price);
    }

    public function test_applies_flat_price_rule(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Flat Price List',
            'slug' => 'flat-price-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Flat,
            'value' => '80.00000000',
            'is_active' => true,
            'priority' => 10,
        ]);

        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        $this->assertSame('80.00000000', $price);
    }

    public function test_applies_percentage_discount_rule(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Discount List',
            'slug' => 'discount-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // -10% off base price (negative value = discount)
        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Percentage,
            'value' => '-10.00000000',
            'is_active' => true,
            'priority' => 10,
        ]);

        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        // 100 + (100 * -10 / 100) = 90
        $this->assertSame('90.00000000', $price);
    }

    public function test_higher_priority_rule_wins(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Priority List',
            'slug' => 'priority-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Flat,
            'value' => '90.00000000',
            'is_active' => true,
            'priority' => 5,
        ]);

        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Flat,
            'value' => '75.00000000',
            'is_active' => true,
            'priority' => 10,
        ]);

        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        $this->assertSame('75.00000000', $price);
    }

    public function test_quantity_threshold_rule_applies_only_within_range(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Bulk List',
            'slug' => 'bulk-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Flat,
            'value' => '70.00000000',
            'min_quantity' => '10',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Qty 5 - should NOT apply tiered rule
        $priceSmall = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '5',
            'currency' => 'USD',
        ]);
        $this->assertSame('100.00000000', $priceSmall);

        // Qty 10 - should apply tiered rule
        $priceLarge = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '10',
            'currency' => 'USD',
        ]);
        $this->assertSame('70.00000000', $priceLarge);
    }

    public function test_applies_conditional_rule_with_flat_delta(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Conditional List',
            'slug' => 'conditional-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Conditional rule: add +5 to base price
        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Conditional,
            'value' => '0',
            'conditions' => ['adjustment' => ['type' => 'flat', 'value' => '5']],
            'is_active' => true,
            'priority' => 10,
        ]);

        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        // base 100 + flat delta 5 = 105
        $this->assertSame('105.00000000', $price);
    }

    public function test_applies_conditional_rule_with_percentage_adjustment(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cond Pct List',
            'slug' => 'cond-pct-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Conditional rule: percentage type, +20%
        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Conditional,
            'value' => '0',
            'conditions' => ['adjustment' => ['type' => 'percentage', 'value' => '20']],
            'is_active' => true,
            'priority' => 10,
        ]);

        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        // base 100 + (100 * 20 / 100) = 120
        $this->assertSame('120.00000000', $price);
    }

    public function test_applies_conditional_rule_with_fixed_price(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cond Fixed List',
            'slug' => 'cond-fixed-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Conditional rule: fixed type, override to 95
        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Conditional,
            'value' => '0',
            'conditions' => ['adjustment' => ['type' => 'fixed', 'value' => '95']],
            'is_active' => true,
            'priority' => 10,
        ]);

        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        $this->assertSame('95.00000000', $price);
    }

    public function test_inactive_rule_is_ignored(): void
    {
        $priceList = PriceList::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive List',
            'slug' => 'inactive-list',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        PriceRule::create([
            'tenant_id' => $this->tenant->id,
            'price_list_id' => $priceList->id,
            'product_id' => $this->product->id,
            'pricing_type' => PricingType::Flat,
            'value' => '50.00000000',
            'is_active' => false,
            'priority' => 10,
        ]);

        $price = $this->engine->calculate([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => '1',
            'currency' => 'USD',
        ]);

        $this->assertSame('100.00000000', $price);
    }
}
