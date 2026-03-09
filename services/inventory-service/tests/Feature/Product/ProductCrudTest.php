<?php
namespace Tests\Feature\Product;
use Tests\TestCase;
use App\Domain\Models\Product;
use App\Domain\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $tenantId = 'tenant_test';
    private array  $authHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authHeaders = [
            'X-Tenant-ID'   => $this->tenantId,
            'Authorization' => 'Bearer test_token',
            'Accept'        => 'application/json',
        ];
    }

    public function test_can_create_product(): void
    {
        $data = [
            'name'           => 'Test Product',
            'sku'            => 'TEST-001',
            'unit_price'     => 99.99,
            'unit_of_measure'=> 'unit',
            'is_active'      => true,
        ];
        $response = $this->postJson('/api/v1/products', $data, $this->authHeaders);
        $response->assertStatus(201)->assertJsonPath('data.sku', 'TEST-001');
    }

    public function test_sku_is_unique_per_tenant(): void
    {
        Product::factory()->create(['tenant_id' => $this->tenantId, 'sku' => 'DUPE-001']);
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Dup', 'sku' => 'DUPE-001', 'unit_price' => 10, 'unit_of_measure' => 'unit',
        ], $this->authHeaders);
        $response->assertStatus(422);
    }

    public function test_can_list_products_with_pagination(): void
    {
        Product::factory()->count(5)->create(['tenant_id' => $this->tenantId]);
        $response = $this->getJson('/api/v1/products?per_page=2', $this->authHeaders);
        $response->assertStatus(200)->assertJsonStructure(['data', 'meta' => ['total','per_page','current_page']]);
        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);
        $response = $this->putJson("/api/v1/products/{$product->id}", ['name' => 'Updated'], $this->authHeaders);
        $response->assertStatus(200)->assertJsonPath('data.name', 'Updated');
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);
        $this->deleteJson("/api/v1/products/{$product->id}", [], $this->authHeaders)->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cannot_access_other_tenant_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => 'other_tenant']);
        $response = $this->getJson("/api/v1/products/{$product->id}", $this->authHeaders);
        $response->assertStatus(404);
    }
}
