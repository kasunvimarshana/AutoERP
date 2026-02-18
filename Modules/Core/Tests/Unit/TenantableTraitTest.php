<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Entities\Customer;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

/**
 * Tenantable Trait Tests
 *
 * Tests for native multi-tenant data isolation system
 */
class TenantableTraitTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);
    }

    public function test_it_automatically_sets_tenant_id_on_create(): void
    {
        tenancy()->initialize($this->tenant1);

        $customer = Customer::factory()->create(['name' => 'Test Customer']);

        $this->assertEquals($this->tenant1->id, $customer->tenant_id);
    }

    public function test_it_only_retrieves_current_tenant_records(): void
    {
        tenancy()->initialize($this->tenant1);
        Customer::factory()->count(3)->create();

        tenancy()->initialize($this->tenant2);
        Customer::factory()->count(2)->create();

        // Switch back to tenant1
        tenancy()->initialize($this->tenant1);
        $tenant1Customers = Customer::all();

        $this->assertCount(3, $tenant1Customers);

        // Switch to tenant2
        tenancy()->initialize($this->tenant2);
        $tenant2Customers = Customer::all();

        $this->assertCount(2, $tenant2Customers);
    }

    public function test_it_prevents_cross_tenant_data_access(): void
    {
        tenancy()->initialize($this->tenant1);
        $customer = Customer::factory()->create();

        // Switch to different tenant
        tenancy()->initialize($this->tenant2);

        // Try to find tenant1's customer
        $foundCustomer = Customer::find($customer->id);

        $this->assertNull($foundCustomer);
    }

    public function test_it_can_bypass_tenant_scope_for_admin(): void
    {
        tenancy()->initialize($this->tenant1);
        Customer::factory()->count(2)->create();

        tenancy()->initialize($this->tenant2);
        Customer::factory()->count(3)->create();

        // Admin query without tenant scope
        $allCustomers = Customer::withoutGlobalScopes()->get();

        $this->assertCount(5, $allCustomers);
    }

    public function test_it_filters_queries_by_tenant(): void
    {
        tenancy()->initialize($this->tenant1);
        Customer::factory()->create(['name' => 'Customer A', 'status' => 'active']);
        Customer::factory()->create(['name' => 'Customer B', 'status' => 'inactive']);

        tenancy()->initialize($this->tenant2);
        Customer::factory()->create(['name' => 'Customer C', 'status' => 'active']);

        // Query from tenant1
        tenancy()->initialize($this->tenant1);
        $activeCustomers = Customer::where('status', 'active')->get();

        $this->assertCount(1, $activeCustomers);
        $this->assertEquals('Customer A', $activeCustomers->first()->name);
    }

    public function test_it_updates_only_within_tenant_scope(): void
    {
        tenancy()->initialize($this->tenant1);
        $customer = Customer::factory()->create(['name' => 'Original Name']);

        // Switch tenant and try to update
        tenancy()->initialize($this->tenant2);

        // Should not be able to find and update
        $foundCustomer = Customer::find($customer->id);
        $this->assertNull($foundCustomer);

        // Switch back to correct tenant
        tenancy()->initialize($this->tenant1);
        $customer->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $customer->fresh()->name);
    }

    public function test_it_deletes_only_within_tenant_scope(): void
    {
        tenancy()->initialize($this->tenant1);
        $customer = Customer::factory()->create();

        // Switch tenant
        tenancy()->initialize($this->tenant2);

        // Should not be able to find and delete
        $foundCustomer = Customer::find($customer->id);
        $this->assertNull($foundCustomer);

        // Switch back
        tenancy()->initialize($this->tenant1);
        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_it_counts_records_per_tenant(): void
    {
        tenancy()->initialize($this->tenant1);
        Customer::factory()->count(5)->create();

        tenancy()->initialize($this->tenant2);
        Customer::factory()->count(3)->create();

        // Check counts
        tenancy()->initialize($this->tenant1);
        $this->assertEquals(5, Customer::count());

        tenancy()->initialize($this->tenant2);
        $this->assertEquals(3, Customer::count());
    }

    public function test_it_works_with_paginated_queries(): void
    {
        tenancy()->initialize($this->tenant1);
        Customer::factory()->count(25)->create();

        $page1 = Customer::paginate(10);
        $this->assertCount(10, $page1->items());
        $this->assertEquals(25, $page1->total());

        // All paginated results should belong to tenant1
        foreach ($page1->items() as $customer) {
            $this->assertEquals($this->tenant1->id, $customer->tenant_id);
        }
    }

    public function test_it_prevents_tenant_id_manipulation(): void
    {
        tenancy()->initialize($this->tenant1);

        // Try to create a record for different tenant
        $customer = Customer::factory()->make([
            'tenant_id' => $this->tenant2->id, // Try to set different tenant
        ]);

        $customer->save();

        // Should be overridden to current tenant
        $this->assertEquals($this->tenant1->id, $customer->tenant_id);
    }

    public function test_it_handles_tenant_switching_correctly(): void
    {
        // Create data for both tenants
        tenancy()->initialize($this->tenant1);
        $customer1 = Customer::factory()->create(['name' => 'Tenant 1 Customer']);

        tenancy()->initialize($this->tenant2);
        $customer2 = Customer::factory()->create(['name' => 'Tenant 2 Customer']);

        // Switch back and forth
        tenancy()->initialize($this->tenant1);
        $found1 = Customer::where('name', 'Tenant 1 Customer')->first();
        $notFound2 = Customer::where('name', 'Tenant 2 Customer')->first();

        $this->assertNotNull($found1);
        $this->assertNull($notFound2);

        tenancy()->initialize($this->tenant2);
        $found2 = Customer::where('name', 'Tenant 2 Customer')->first();
        $notFound1 = Customer::where('name', 'Tenant 1 Customer')->first();

        $this->assertNotNull($found2);
        $this->assertNull($notFound1);
    }
}
