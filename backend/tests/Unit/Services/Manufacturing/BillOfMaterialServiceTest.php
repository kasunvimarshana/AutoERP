<?php

namespace Tests\Unit\Services\Manufacturing;

use App\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use App\Modules\Manufacturing\DTOs\CreateBillOfMaterialDTO;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Repositories\BillOfMaterialRepository;
use App\Modules\Manufacturing\Services\BillOfMaterialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Unit\UnitTestCase;

class BillOfMaterialServiceTest extends UnitTestCase
{
    use RefreshDatabase;

    protected BillOfMaterialService $service;

    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(BillOfMaterialRepository::class);
        $this->service = new BillOfMaterialService($this->repositoryMock);
    }

    public function test_can_create_bom(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->forTenant($tenant)->create();

        $dto = new CreateBillOfMaterialDTO(
            productId: $product->id,
            bomNumber: 'BOM-001',
            version: 1,
            isActive: true,
            effectiveDate: now()->toDateString(),
            notes: 'Test BOM',
            items: [
                [
                    'component_product_id' => $product->id,
                    'quantity' => 2,
                    'uom_id' => null,
                    'scrap_factor' => 0,
                    'notes' => null,
                ],
            ]
        );

        $bom = BillOfMaterial::factory()->make([
            'id' => 1,
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'bom_number' => 'BOM-001',
        ]);
        $bom->exists = true; // Mark as persisted

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($bom);

        $result = $this->service->createBOM($dto);

        $this->assertInstanceOf(BillOfMaterial::class, $result);
        $this->assertEquals('BOM-001', $result->bom_number);
    }

    public function test_can_delete_bom(): void
    {
        $bomId = 1;

        $this->repositoryMock
            ->shouldReceive('delete')
            ->with($bomId)
            ->once()
            ->andReturn(true);

        $result = $this->service->deleteBOM($bomId);

        $this->assertTrue($result);
    }

    public function test_can_get_bom_by_id(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->forTenant($tenant)->create();
        $bom = BillOfMaterial::factory()->forTenant($tenant)->forProduct($product)->make(['id' => 1]);

        $this->repositoryMock
            ->shouldReceive('findWithItems')
            ->with($bom->id)
            ->once()
            ->andReturn($bom);

        $result = $this->service->getBOMById($bom->id);

        $this->assertInstanceOf(BillOfMaterial::class, $result);
        $this->assertEquals($bom->id, $result->id);
    }
}
