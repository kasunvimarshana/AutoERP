<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Branch;
use Modules\Pricing\Models\PriceList;
use Modules\Product\Models\Product;
use Tests\TestCase;

class PricingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
    }

    public function test_can_calculate_flat_price(): void
    {
        $product = Product::factory()->create([
            'selling_price' => 100.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/pricing/calculate', [
                'product_id' => $product->id,
                'quantity' => 2,
                'strategy' => 'flat',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'product_id',
                    'quantity',
                    'unit_price',
                    'subtotal',
                ],
            ]);
    }

    public function test_can_create_price_list(): void
    {
        $data = [
            'name' => 'Test Price List',
            'code' => 'TEST001',
            'currency_code' => 'USD',
            'is_default' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/price-lists', $data);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Test Price List',
                'code' => 'TEST001',
            ]);

        $this->assertDatabaseHas('price_lists', [
            'code' => 'TEST001',
        ]);
    }

    public function test_can_list_price_lists(): void
    {
        PriceList::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/price-lists');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }
}
