<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Models\Supplier;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Supplier API Feature Test
 *
 * Tests Supplier API endpoints
 */
class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'supplier.view']);
        Permission::create(['name' => 'supplier.create']);
        Permission::create(['name' => 'supplier.edit']);
        Permission::create(['name' => 'supplier.delete']);

        // Create role with permissions
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(['supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete']);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    /**
     * Test can list suppliers
     */
    public function test_can_list_suppliers(): void
    {
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/suppliers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'supplier_code',
                        'supplier_name',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test can create supplier
     */
    public function test_can_create_supplier(): void
    {
        $supplierData = [
            'supplier_name' => 'Test Supplier',
            'contact_person' => 'John Doe',
            'email' => 'john@supplier.com',
            'phone' => '1234567890',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/suppliers', $supplierData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'supplier_code',
                    'supplier_name',
                ],
            ]);

        $this->assertDatabaseHas('suppliers', [
            'supplier_name' => 'Test Supplier',
            'email' => 'john@supplier.com',
        ]);
    }

    /**
     * Test can update supplier
     */
    public function test_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $updateData = [
            'supplier_name' => 'Updated Supplier',
            'status' => 'inactive',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/suppliers/{$supplier->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'supplier_name' => 'Updated Supplier',
            'status' => 'inactive',
        ]);
    }

    /**
     * Test can delete supplier
     */
    public function test_can_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }
}
