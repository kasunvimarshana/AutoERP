<?php

namespace Tests\Feature\Pricing;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Product\Models\Product;
use App\Models\PricingRule;
use App\Models\DiscountTier;
use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use Database\Seeders\TenantSeeder;
use Database\Seeders\UserSeeder;

class PricingCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed tenants and users
        $this->seed(TenantSeeder::class);
        $this->seed(UserSeeder::class);

        // Get a user for authentication
        $this->user = User::where('email', 'admin@demo.autoerp.local')->first();
        $this->tenant = $this->user->tenant;
    }

    public function test_can_calculate_basic_product_price(): void
    {
        // Create a simple product
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'inventory',
            'is_active' => true,
            'selling_price' => 100.00,
            'buying_price' => 60.00,
            'selling_discount_type' => 'none',
            'created_by' => $this->user->id,
        ]);

        // Make authenticated request
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/pricing/calculate', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'product_id',
                    'quantity',
                    'base_price',
                    'final_price',
                    'total_amount',
                    'profit_margin',
                    'profit_margin_percentage',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(100.00, $data['base_price']);
        $this->assertEquals(100.00, $data['final_price']);
    }

    public function test_can_calculate_price_with_percentage_discount(): void
    {
        // Create a product with percentage discount
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'TEST-002',
            'name' => 'Discounted Product',
            'slug' => 'discounted-product',
            'type' => 'inventory',
            'is_active' => true,
            'selling_price' => 100.00,
            'buying_price' => 60.00,
            'selling_discount_type' => 'percentage',
            'selling_discount_value' => 10.00, // 10% discount
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/pricing/calculate', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(100.00, $data['base_price']);
        $this->assertEquals(90.00, $data['discounted_price']); // 100 - 10%
        $this->assertEquals(10.00, $data['discount_amount']);
    }

    public function test_can_calculate_bulk_pricing(): void
    {
        // Create multiple products
        $product1 = Product::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BULK-001',
            'name' => 'Bulk Product 1',
            'slug' => 'bulk-product-1',
            'type' => 'inventory',
            'is_active' => true,
            'selling_price' => 50.00,
            'created_by' => $this->user->id,
        ]);

        $product2 = Product::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BULK-002',
            'name' => 'Bulk Product 2',
            'slug' => 'bulk-product-2',
            'type' => 'inventory',
            'is_active' => true,
            'selling_price' => 75.00,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/pricing/calculate-bulk', [
                'items' => [
                    ['product_id' => $product1->id, 'quantity' => 2],
                    ['product_id' => $product2->id, 'quantity' => 3],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items',
                    'summary' => [
                        'grand_total',
                        'item_count',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data['items']);
        $this->assertEquals(2, $data['summary']['item_count']);
        // 2 * 50 + 3 * 75 = 325
        $this->assertEquals(325.00, $data['summary']['grand_total']);
    }
}
