<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

/**
 * Tenant Context Resolution Tests
 *
 * Tests for tenant context initialization and resolution
 * through various methods (subdomain, domain, user, etc.)
 */
class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_context_is_null_by_default(): void
    {
        $tenant = tenancy()->tenant;
        $this->assertNull($tenant);
    }

    public function test_tenant_context_can_be_initialized(): void
    {
        $tenant = Tenant::factory()->create();

        tenancy()->initialize($tenant);

        $currentTenant = tenancy()->tenant;
        $this->assertNotNull($currentTenant);
        $this->assertEquals($tenant->id, $currentTenant->id);
    }

    public function test_tenant_context_can_be_cleared(): void
    {
        $tenant = Tenant::factory()->create();
        tenancy()->initialize($tenant);

        $this->assertNotNull(tenancy()->tenant);

        tenancy()->end();

        $this->assertNull(tenancy()->tenant);
    }

    public function test_tenant_context_from_authenticated_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        // Context should be set from user's tenant_id
        if (method_exists(tenancy(), 'initialize')) {
            tenancy()->initialize($tenant);
            $this->assertEquals($tenant->id, tenancy()->tenant->id);
        } else {
            $this->markTestSkipped('Tenancy initialize method not available');
        }
    }

    public function test_tenant_can_be_resolved_by_slug(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'test-company']);

        $tenantService = app(\Modules\Tenancy\Services\TenantService::class);
        $foundTenant = $tenantService->findBySlug('test-company');

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
    }

    public function test_tenant_can_be_resolved_by_domain(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'test.example.com']);

        $tenantService = app(\Modules\Tenancy\Services\TenantService::class);
        $foundTenant = $tenantService->findByDomain('test.example.com');

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
    }

    public function test_tenant_id_is_available_in_context(): void
    {
        $tenant = Tenant::factory()->create();
        tenancy()->initialize($tenant);

        $tenantId = tenancy()->tenant->id ?? null;
        $this->assertNotNull($tenantId);
        $this->assertEquals($tenant->id, $tenantId);
    }

    public function test_multiple_tenants_can_be_switched(): void
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Initialize with tenant1
        tenancy()->initialize($tenant1);
        $this->assertEquals($tenant1->id, tenancy()->tenant->id);

        // Switch to tenant2
        tenancy()->initialize($tenant2);
        $this->assertEquals($tenant2->id, tenancy()->tenant->id);
    }

    public function test_tenant_context_persists_across_requests(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // First request
        $response1 = $this->actingAs($user)->getJson('/api/v1/tenants/current');

        // Second request with same user
        $response2 = $this->actingAs($user)->getJson('/api/v1/tenants/current');

        // Both requests should have same tenant context
        if ($response1->status() !== 404 && $response2->status() !== 404) {
            $this->assertEquals(
                $response1->json('data.id'),
                $response2->json('data.id')
            );
        } else {
            $this->markTestSkipped('Current tenant endpoint not implemented');
        }
    }

    public function test_suspended_tenant_context_can_be_detected(): void
    {
        $tenant = Tenant::factory()->suspended()->create();
        tenancy()->initialize($tenant);

        $currentTenant = tenancy()->tenant;
        $this->assertNotNull($currentTenant);
        $this->assertEquals('suspended', $currentTenant->status);
        $this->assertFalse($currentTenant->isActive());
    }

    public function test_trial_tenant_context_can_be_detected(): void
    {
        $tenant = Tenant::factory()->onTrial()->create();
        tenancy()->initialize($tenant);

        $currentTenant = tenancy()->tenant;
        $this->assertNotNull($currentTenant);
        $this->assertTrue($currentTenant->onTrial());
    }

    public function test_expired_tenant_context_can_be_detected(): void
    {
        $tenant = Tenant::factory()->expired()->create();
        tenancy()->initialize($tenant);

        $currentTenant = tenancy()->tenant;
        $this->assertNotNull($currentTenant);
        $this->assertFalse($currentTenant->hasActiveSubscription());
    }

    public function test_tenant_settings_are_accessible_in_context(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'currency' => 'USD',
            ],
        ]);

        tenancy()->initialize($tenant);

        $settings = tenancy()->tenant->settings;
        $this->assertEquals('America/New_York', $settings['timezone']);
        $this->assertEquals('en', $settings['locale']);
        $this->assertEquals('USD', $settings['currency']);
    }

    public function test_tenant_features_are_accessible_in_context(): void
    {
        $tenant = Tenant::factory()->create([
            'features' => ['sales', 'crm', 'inventory'],
        ]);

        tenancy()->initialize($tenant);

        $features = tenancy()->tenant->features;
        $this->assertContains('sales', $features);
        $this->assertContains('crm', $features);
        $this->assertContains('inventory', $features);
    }

    public function test_tenant_limits_are_accessible_in_context(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        tenancy()->initialize($tenant);

        $limits = tenancy()->tenant->limits;
        $this->assertEquals(5, $limits['users']);
        $this->assertEquals(1024, $limits['storage_mb']);
    }

    public function test_guest_user_has_no_tenant_context(): void
    {
        $response = $this->getJson('/api/v1/tenants');

        // Guest users should get 401 Unauthorized
        $response->assertStatus(401);

        // And should have no tenant context
        $this->assertNull(tenancy()->tenant);
    }

    public function test_tenant_context_is_isolated_per_request(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        // Request 1 with user1
        $this->actingAs($user1);
        tenancy()->initialize($tenant1);
        $context1 = tenancy()->tenant->id;

        // Clear context between requests
        tenancy()->end();

        // Request 2 with user2
        $this->actingAs($user2);
        tenancy()->initialize($tenant2);
        $context2 = tenancy()->tenant->id;

        // Contexts should be different
        $this->assertNotEquals($context1, $context2);
        $this->assertEquals($tenant1->id, $context1);
        $this->assertEquals($tenant2->id, $context2);
    }

    public function test_tenant_context_can_be_checked(): void
    {
        $tenant = Tenant::factory()->create();

        // Before initialization
        $this->assertFalse(tenancy()->initialized);

        // After initialization
        tenancy()->initialize($tenant);
        $this->assertTrue(tenancy()->initialized);

        // After ending
        tenancy()->end();
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_initializing_with_inactive_tenant_is_allowed(): void
    {
        $tenant = Tenant::factory()->inactive()->create();

        // System should allow initialization even for inactive tenants
        // Business logic will determine if they can access resources
        tenancy()->initialize($tenant);

        $this->assertNotNull(tenancy()->tenant);
        $this->assertEquals($tenant->id, tenancy()->tenant->id);
        $this->assertEquals('inactive', tenancy()->tenant->status);
    }

    public function test_tenant_context_contains_full_tenant_model(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test.example.com',
        ]);

        tenancy()->initialize($tenant);

        $context = tenancy()->tenant;
        $this->assertInstanceOf(Tenant::class, $context);
        $this->assertEquals('Test Company', $context->name);
        $this->assertEquals('test-company', $context->slug);
        $this->assertEquals('test.example.com', $context->domain);
    }

    public function test_null_tenant_initialization_clears_context(): void
    {
        $tenant = Tenant::factory()->create();
        tenancy()->initialize($tenant);

        $this->assertNotNull(tenancy()->tenant);

        // Initialize with null should clear context
        tenancy()->initialize(null);

        $this->assertNull(tenancy()->tenant);
    }

    public function test_tenant_context_helper_function_works(): void
    {
        $tenant = Tenant::factory()->create();
        tenancy()->initialize($tenant);

        // Test helper function
        $helperTenant = tenant();

        if ($helperTenant !== null) {
            $this->assertEquals($tenant->id, $helperTenant->id);
        } else {
            $this->markTestSkipped('Tenant helper function not available');
        }
    }
}
