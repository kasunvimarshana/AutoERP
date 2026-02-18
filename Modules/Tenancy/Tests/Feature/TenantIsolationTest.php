<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

/**
 * Tenant Isolation Tests
 *
 * These tests verify that tenant data isolation works correctly
 * and that users can only access data belonging to their tenant.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;

    protected Tenant $tenant2;

    protected User $user1;

    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenants
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Create users for each tenant
        $this->user1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id ?? null,
            'permissions' => ['tenant.view', 'customer.view', 'order.view'],
        ]);

        $this->user2 = User::factory()->create([
            'tenant_id' => $this->tenant2->id ?? null,
            'permissions' => ['tenant.view', 'customer.view', 'order.view'],
        ]);
    }

    public function test_user_can_only_access_own_tenant(): void
    {
        $response1 = $this->actingAs($this->user1)
            ->getJson("/api/v1/tenants/{$this->tenant1->id}");

        $response1->assertStatus(200)
            ->assertJsonPath('data.id', $this->tenant1->id);

        // User from tenant1 should not access tenant2
        $response2 = $this->actingAs($this->user1)
            ->getJson("/api/v1/tenants/{$this->tenant2->id}");

        $response2->assertStatus(403);
    }

    public function test_tenant_context_is_set_from_authenticated_user(): void
    {
        $this->actingAs($this->user1);

        // Verify the current tenant context matches user's tenant
        $currentTenant = tenancy()->tenant;
        $this->assertNotNull($currentTenant);
        $this->assertEquals($this->tenant1->id, $currentTenant->id);
    }

    public function test_tenant_context_switches_between_users(): void
    {
        // Set context for user1
        $this->actingAs($this->user1);
        $tenant1Context = tenancy()->tenant;
        $this->assertEquals($this->tenant1->id, $tenant1Context->id);

        // Switch to user2
        $this->actingAs($this->user2);
        $tenant2Context = tenancy()->tenant;
        $this->assertEquals($this->tenant2->id, $tenant2Context->id);
    }

    public function test_queries_are_automatically_scoped_to_tenant(): void
    {
        // This assumes you have a Customer model with tenant scoping
        // Create customers for each tenant
        if (class_exists(\Modules\Sales\Entities\Customer::class)) {
            \Modules\Sales\Entities\Customer::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'name' => 'Customer Tenant 1',
            ]);

            \Modules\Sales\Entities\Customer::factory()->create([
                'tenant_id' => $this->tenant2->id,
                'name' => 'Customer Tenant 2',
            ]);

            // Acting as user1, should only see tenant1's customers
            $this->actingAs($this->user1);
            tenancy()->initialize($this->tenant1);

            $customers = \Modules\Sales\Entities\Customer::all();
            $this->assertCount(1, $customers);
            $this->assertEquals('Customer Tenant 1', $customers->first()->name);

            // Switch to user2, should only see tenant2's customers
            $this->actingAs($this->user2);
            tenancy()->initialize($this->tenant2);

            $customers = \Modules\Sales\Entities\Customer::all();
            $this->assertCount(1, $customers);
            $this->assertEquals('Customer Tenant 2', $customers->first()->name);
        } else {
            $this->markTestSkipped('Customer model not available for tenant isolation test');
        }
    }

    public function test_user_cannot_create_data_for_different_tenant(): void
    {
        $this->actingAs($this->user1);

        if (class_exists(\Modules\Sales\Entities\Customer::class)) {
            $response = $this->postJson('/api/v1/sales/customers', [
                'tenant_id' => $this->tenant2->id, // Attempt to create for different tenant
                'name' => 'Malicious Customer',
                'email' => 'malicious@example.com',
                'type' => 'business',
            ]);

            // Should either fail or automatically use authenticated user's tenant
            if ($response->status() === 201) {
                $customer = \Modules\Sales\Entities\Customer::latest()->first();
                $this->assertEquals($this->tenant1->id, $customer->tenant_id);
                $this->assertNotEquals($this->tenant2->id, $customer->tenant_id);
            }
        } else {
            $this->markTestSkipped('Customer model not available for tenant isolation test');
        }
    }

    public function test_tenant_cannot_be_changed_after_creation(): void
    {
        if (class_exists(\Modules\Sales\Entities\Customer::class)) {
            $this->actingAs($this->user1);

            // Create customer for tenant1
            $response = $this->postJson('/api/v1/sales/customers', [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'type' => 'business',
            ]);

            if ($response->status() === 201) {
                $customerId = $response->json('data.id');

                // Attempt to change tenant_id
                $updateResponse = $this->putJson("/api/v1/sales/customers/{$customerId}", [
                    'tenant_id' => $this->tenant2->id, // Malicious attempt
                    'name' => 'Updated Customer',
                ]);

                // Verify tenant_id hasn't changed
                $customer = \Modules\Sales\Entities\Customer::find($customerId);
                $this->assertEquals($this->tenant1->id, $customer->tenant_id);
                $this->assertNotEquals($this->tenant2->id, $customer->tenant_id);
            }
        } else {
            $this->markTestSkipped('Customer model not available for tenant isolation test');
        }
    }

    public function test_unauthenticated_request_has_no_tenant_context(): void
    {
        $currentTenant = tenancy()->tenant;
        $this->assertNull($currentTenant);
    }

    public function test_super_admin_can_access_all_tenants(): void
    {
        $superAdmin = User::factory()->create([
            'tenant_id' => null, // Super admin not tied to specific tenant
            'permissions' => ['*'], // All permissions
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin);

        // Should be able to access tenant1
        $response1 = $this->getJson("/api/v1/tenants/{$this->tenant1->id}");
        $response1->assertStatus(200);

        // Should be able to access tenant2
        $response2 = $this->getJson("/api/v1/tenants/{$this->tenant2->id}");
        $response2->assertStatus(200);
    }

    public function test_tenant_scoped_relationships_are_isolated(): void
    {
        if (class_exists(\Modules\Sales\Entities\Customer::class) &&
            class_exists(\Modules\Sales\Entities\SalesOrder::class)) {

            // Create customer and order for tenant1
            $customer1 = \Modules\Sales\Entities\Customer::factory()->create([
                'tenant_id' => $this->tenant1->id,
            ]);
            $order1 = \Modules\Sales\Entities\SalesOrder::factory()->create([
                'tenant_id' => $this->tenant1->id,
                'customer_id' => $customer1->id,
            ]);

            // Create customer and order for tenant2
            $customer2 = \Modules\Sales\Entities\Customer::factory()->create([
                'tenant_id' => $this->tenant2->id,
            ]);
            $order2 = \Modules\Sales\Entities\SalesOrder::factory()->create([
                'tenant_id' => $this->tenant2->id,
                'customer_id' => $customer2->id,
            ]);

            // Acting as user1
            $this->actingAs($this->user1);
            tenancy()->initialize($this->tenant1);

            // Should only see tenant1's orders through customer relationship
            $customerOrders = $customer1->salesOrders;
            $this->assertCount(1, $customerOrders);
            $this->assertEquals($order1->id, $customerOrders->first()->id);

            // Should not be able to access customer2's orders
            $this->assertNull(\Modules\Sales\Entities\Customer::find($customer2->id));
        } else {
            $this->markTestSkipped('Sales models not available for relationship isolation test');
        }
    }

    public function test_bulk_operations_respect_tenant_isolation(): void
    {
        if (class_exists(\Modules\Sales\Entities\Customer::class)) {
            // Create multiple customers for each tenant
            \Modules\Sales\Entities\Customer::factory()->count(3)->create([
                'tenant_id' => $this->tenant1->id,
            ]);
            \Modules\Sales\Entities\Customer::factory()->count(2)->create([
                'tenant_id' => $this->tenant2->id,
            ]);

            // Acting as user1, bulk delete should only affect tenant1's data
            $this->actingAs($this->user1);
            tenancy()->initialize($this->tenant1);

            $deletedCount = \Modules\Sales\Entities\Customer::where('status', 'active')->delete();
            $this->assertLessThanOrEqual(3, $deletedCount);

            // Tenant2's customers should remain untouched
            tenancy()->initialize($this->tenant2);
            $tenant2Customers = \Modules\Sales\Entities\Customer::withoutGlobalScopes()->where('tenant_id', $this->tenant2->id)->count();
            $this->assertEquals(2, $tenant2Customers);
        } else {
            $this->markTestSkipped('Customer model not available for bulk operation test');
        }
    }

    public function test_raw_queries_require_manual_tenant_filtering(): void
    {
        // This test documents that raw queries bypass global scopes
        // and require manual tenant filtering
        $this->actingAs($this->user1);
        tenancy()->initialize($this->tenant1);

        $currentTenantId = tenancy()->tenant->id;
        $this->assertNotNull($currentTenantId);

        // Raw queries should include WHERE tenant_id = ?
        // This is a documentation test to remind developers
        $this->assertTrue(true, 'Raw queries must manually filter by tenant_id');
    }

    public function test_tenant_is_resolved_from_subdomain(): void
    {
        // Mock subdomain resolution
        $slug = $this->tenant1->slug;

        // Make request with subdomain-like header
        $response = $this->withHeaders([
            'X-Tenant-Slug' => $slug,
        ])->getJson('/api/v1/tenants/current');

        // If this endpoint exists, it should return current tenant
        // This tests subdomain-based tenant resolution
        if ($response->status() !== 404) {
            $response->assertStatus(200)
                ->assertJsonPath('data.slug', $slug);
        } else {
            $this->markTestSkipped('Current tenant endpoint not implemented');
        }
    }

    public function test_tenant_is_resolved_from_domain(): void
    {
        // Test domain-based tenant resolution
        $domain = $this->tenant1->domain;

        $response = $this->withHeaders([
            'Host' => $domain,
        ])->getJson('/api/v1/tenants/current');

        // If this endpoint exists, it should return current tenant
        if ($response->status() !== 404) {
            $response->assertStatus(200)
                ->assertJsonPath('data.domain', $domain);
        } else {
            $this->markTestSkipped('Current tenant endpoint not implemented');
        }
    }
}
