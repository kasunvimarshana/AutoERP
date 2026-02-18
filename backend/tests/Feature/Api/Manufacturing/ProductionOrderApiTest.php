<?php

namespace Tests\Feature\Api\Manufacturing;

use App\Modules\Inventory\Models\Product;
use App\Modules\Manufacturing\Enums\ProductionOrderStatus;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Models\ProductionOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;

class ProductionOrderApiTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected string $baseUrl = '/api/v1/manufacturing/production-orders';

    public function test_can_list_production_orders(): void
    {
        $this->actingAsUser();

        ProductionOrder::factory()
            ->forTenant($this->tenant)
            ->count(3)
            ->create();

        $response = $this->getJson($this->baseUrl);

        $this->assertJsonSuccess($response)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'production_order_number', 'product_id', 'quantity', 'status'],
                    ],
                ],
            ]);
    }

    public function test_can_create_production_order(): void
    {
        $this->actingAsUser();

        $product = Product::factory()->forTenant($this->tenant)->create();
        $bom = BillOfMaterial::factory()
            ->forTenant($this->tenant)
            ->forProduct($product)
            ->create();

        $data = [
            'production_order_number' => 'MO-TEST-001',
            'product_id' => $product->id,
            'quantity' => 100,
            'bill_of_material_id' => $bom->id,
            'scheduled_start_date' => now()->toDateString(),
            'scheduled_end_date' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'priority' => 'normal',
            'notes' => 'Test production order',
        ];

        $response = $this->postJson($this->baseUrl, $data);

        $this->assertJsonSuccess($response, 201)
            ->assertJsonFragment(['production_order_number' => 'MO-TEST-001']);
    }

    public function test_can_show_production_order(): void
    {
        $this->actingAsUser();

        $po = ProductionOrder::factory()
            ->forTenant($this->tenant)
            ->create();

        $response = $this->getJson("{$this->baseUrl}/{$po->id}");

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['id' => $po->id]);
    }

    public function test_can_release_production_order(): void
    {
        $this->actingAsUser();

        $po = ProductionOrder::factory()
            ->forTenant($this->tenant)
            ->draft()
            ->create();

        $response = $this->postJson("{$this->baseUrl}/{$po->id}/release");

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['status' => ProductionOrderStatus::RELEASED->value]);
    }

    public function test_can_cancel_production_order(): void
    {
        $this->actingAsUser();

        $po = ProductionOrder::factory()
            ->forTenant($this->tenant)
            ->draft()
            ->create();

        $response = $this->postJson("{$this->baseUrl}/{$po->id}/cancel");

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['status' => ProductionOrderStatus::CANCELLED->value]);
    }

    public function test_cannot_release_non_draft_production_order(): void
    {
        $this->actingAsUser();

        $po = ProductionOrder::factory()
            ->forTenant($this->tenant)
            ->released()
            ->create();

        $response = $this->postJson("{$this->baseUrl}/{$po->id}/release");

        $response->assertStatus(500); // Should throw exception
    }

    public function test_can_get_in_progress_production_orders(): void
    {
        $this->actingAsUser();

        ProductionOrder::factory()
            ->forTenant($this->tenant)
            ->inProgress()
            ->count(2)
            ->create();

        $response = $this->getJson("{$this->baseUrl}/in-progress");

        $this->assertJsonSuccess($response);
    }
}
