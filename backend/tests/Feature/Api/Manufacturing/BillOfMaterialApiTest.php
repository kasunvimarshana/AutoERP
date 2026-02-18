<?php

namespace Tests\Feature\Api\Manufacturing;

use App\Modules\Inventory\Models\Product;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;

class BillOfMaterialApiTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected string $baseUrl = '/api/v1/manufacturing/boms';

    public function test_can_list_boms(): void
    {
        $this->actingAsUser();

        BillOfMaterial::factory()
            ->forTenant($this->tenant)
            ->count(3)
            ->create();

        $response = $this->getJson($this->baseUrl);

        $this->assertJsonSuccess($response)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'bom_number', 'product_id', 'version', 'is_active'],
                    ],
                ],
            ]);
    }

    public function test_can_create_bom(): void
    {
        $this->actingAsUser();

        $product = Product::factory()->forTenant($this->tenant)->create();

        $data = [
            'product_id' => $product->id,
            'bom_number' => 'BOM-TEST-001',
            'version' => 1,
            'is_active' => true,
            'effective_date' => now()->toDateString(),
            'notes' => 'Test BOM',
            'items' => [
                [
                    'component_product_id' => $product->id,
                    'quantity' => 2,
                    'scrap_factor' => 0,
                ],
            ],
        ];

        $response = $this->postJson($this->baseUrl, $data);

        $this->assertJsonSuccess($response, 201)
            ->assertJsonFragment(['bom_number' => 'BOM-TEST-001']);
    }

    public function test_can_show_bom(): void
    {
        $this->actingAsUser();

        $bom = BillOfMaterial::factory()
            ->forTenant($this->tenant)
            ->create();

        $response = $this->getJson("{$this->baseUrl}/{$bom->id}");

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['id' => $bom->id]);
    }

    public function test_can_update_bom(): void
    {
        $this->actingAsUser();

        $bom = BillOfMaterial::factory()
            ->forTenant($this->tenant)
            ->create();

        $data = [
            'product_id' => $bom->product_id,
            'bom_number' => 'BOM-UPDATED',
            'version' => 1,
            'is_active' => true,
            'notes' => 'Updated',
        ];

        $response = $this->putJson("{$this->baseUrl}/{$bom->id}", $data);

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['bom_number' => 'BOM-UPDATED']);
    }

    public function test_can_delete_bom(): void
    {
        $this->actingAsUser();

        $bom = BillOfMaterial::factory()
            ->forTenant($this->tenant)
            ->create();

        $response = $this->deleteJson("{$this->baseUrl}/{$bom->id}");

        $this->assertJsonSuccess($response);

        $this->assertDatabaseMissing('bill_of_materials', [
            'id' => $bom->id,
            'deleted_at' => null,
        ]);
    }

    public function test_cannot_create_bom_with_invalid_data(): void
    {
        $this->actingAsUser();

        $data = [
            'bom_number' => '', // Invalid
        ];

        $response = $this->postJson($this->baseUrl, $data);

        $this->assertValidationErrors($response, ['bom_number', 'product_id']);
    }
}
