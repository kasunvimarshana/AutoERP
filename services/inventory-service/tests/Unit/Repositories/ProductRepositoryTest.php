<?php
namespace Tests\Unit\Repositories;
use Tests\TestCase;
use App\Repositories\ProductRepository;
use App\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ProductRepository(new Product());
    }

    public function test_list_filters_by_tenant(): void
    {
        Product::factory()->create(['tenant_id' => 'tenant_a', 'sku' => 'SKU-A1']);
        Product::factory()->create(['tenant_id' => 'tenant_b', 'sku' => 'SKU-B1']);
        $results = $this->repo->list('tenant_a');
        $this->assertCount(1, $results);
        $this->assertEquals('SKU-A1', $results->first()->sku);
    }

    public function test_pagination_works(): void
    {
        Product::factory()->count(10)->create(['tenant_id' => 'tenant_p']);
        $results = $this->repo->list('tenant_p', ['per_page' => 3]);
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $results);
        $this->assertEquals(3, $results->perPage());
        $this->assertEquals(10, $results->total());
    }

    public function test_search_by_name_or_sku(): void
    {
        Product::factory()->create(['tenant_id' => 'tenant_s', 'sku' => 'LAPTOP-001', 'name' => 'Gaming Laptop']);
        Product::factory()->create(['tenant_id' => 'tenant_s', 'sku' => 'MOUSE-001',  'name' => 'Gaming Mouse']);
        $results = $this->repo->searchByNameOrSku('tenant_s', 'Gaming');
        $this->assertCount(2, $results);
    }

    public function test_exists_by_sku(): void
    {
        Product::factory()->create(['tenant_id' => 'tenant_e', 'sku' => 'EXIST-001']);
        $this->assertTrue($this->repo->existsBySku('tenant_e', 'EXIST-001'));
        $this->assertFalse($this->repo->existsBySku('tenant_e', 'NO-SUCH'));
    }
}
