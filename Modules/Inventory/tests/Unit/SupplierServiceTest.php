<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Models\Supplier;
use Modules\Inventory\Repositories\SupplierRepository;
use Modules\Inventory\Services\SupplierService;
use Tests\TestCase;

/**
 * Supplier Service Unit Test
 *
 * Tests Supplier Service business logic
 */
class SupplierServiceTest extends TestCase
{
    use RefreshDatabase;

    private SupplierService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = new SupplierRepository;
        $this->service = new SupplierService($repository);
    }

    /**
     * Test create supplier generates unique code
     */
    public function test_create_supplier_generates_unique_code(): void
    {
        $data = [
            'supplier_name' => 'Test Supplier',
            'status' => 'active',
        ];

        $supplier = $this->service->create($data);

        $this->assertNotNull($supplier->supplier_code);
        $this->assertStringStartsWith('SUP', $supplier->supplier_code);
        $this->assertEquals('Test Supplier', $supplier->supplier_name);
    }

    /**
     * Test create supplier with provided code
     */
    public function test_create_supplier_with_provided_code(): void
    {
        $data = [
            'supplier_code' => 'SUP12345',
            'supplier_name' => 'Test Supplier',
            'status' => 'active',
        ];

        $supplier = $this->service->create($data);

        $this->assertEquals('SUP12345', $supplier->supplier_code);
    }

    /**
     * Test update supplier
     */
    public function test_update_supplier(): void
    {
        $supplier = Supplier::factory()->create(['supplier_name' => 'Original Name']);

        $updated = $this->service->update($supplier->id, [
            'supplier_name' => 'Updated Name',
        ]);

        $this->assertEquals('Updated Name', $updated->supplier_name);
    }

    /**
     * Test get active suppliers
     */
    public function test_get_active_suppliers(): void
    {
        Supplier::factory()->create(['status' => 'active']);
        Supplier::factory()->create(['status' => 'active']);
        Supplier::factory()->create(['status' => 'inactive']);

        $activeSuppliers = $this->service->getActive();

        $this->assertCount(2, $activeSuppliers);
    }
}
