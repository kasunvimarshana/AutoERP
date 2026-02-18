<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Tenancy\Entities\Tenant;
use Modules\Tenancy\Events\TenantCreated;
use Modules\Tenancy\Events\TenantDeleted;
use Modules\Tenancy\Events\TenantUpdated;
use Modules\Tenancy\Services\TenantService;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantService $tenantService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantService = app(TenantService::class);
    }

    public function test_it_creates_tenant_successfully(): void
    {
        Event::fake([TenantCreated::class]);

        $data = [
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test-company.example.com',
            'database' => 'tenant_test_company',
            'schema' => 'test_company',
        ];

        $tenant = $this->tenantService->create($data);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('Test Company', $tenant->name);
        $this->assertEquals('test-company', $tenant->slug);
        $this->assertEquals('active', $tenant->status); // Default status

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Company',
            'slug' => 'test-company',
            'status' => 'active',
        ]);

        Event::assertDispatched(TenantCreated::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    public function test_it_sets_default_status_when_creating_tenant(): void
    {
        $data = [
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test-company.example.com',
        ];

        $tenant = $this->tenantService->create($data);

        $this->assertEquals('active', $tenant->status);
    }

    public function test_it_updates_tenant_successfully(): void
    {
        Event::fake([TenantUpdated::class]);

        $tenant = Tenant::factory()->create();

        $updateData = [
            'name' => 'Updated Company Name',
            'status' => 'suspended',
        ];

        $updatedTenant = $this->tenantService->update($tenant->id, $updateData);

        $this->assertEquals('Updated Company Name', $updatedTenant->name);
        $this->assertEquals('suspended', $updatedTenant->status);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Company Name',
            'status' => 'suspended',
        ]);

        Event::assertDispatched(TenantUpdated::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    public function test_it_deletes_tenant_successfully(): void
    {
        Event::fake([TenantDeleted::class]);

        $tenant = Tenant::factory()->create();

        $result = $this->tenantService->delete($tenant->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);

        Event::assertDispatched(TenantDeleted::class);
    }

    public function test_it_returns_false_when_deleting_nonexistent_tenant(): void
    {
        $result = $this->tenantService->delete(99999);

        $this->assertFalse($result);
    }

    public function test_it_finds_tenant_by_id(): void
    {
        $tenant = Tenant::factory()->create();

        $foundTenant = $this->tenantService->findById($tenant->id);

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
        $this->assertEquals($tenant->name, $foundTenant->name);
    }

    public function test_it_returns_null_when_tenant_not_found_by_id(): void
    {
        $foundTenant = $this->tenantService->findById(99999);

        $this->assertNull($foundTenant);
    }

    public function test_it_finds_tenant_by_slug(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'unique-slug']);

        $foundTenant = $this->tenantService->findBySlug('unique-slug');

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
        $this->assertEquals('unique-slug', $foundTenant->slug);
    }

    public function test_it_returns_null_when_tenant_not_found_by_slug(): void
    {
        $foundTenant = $this->tenantService->findBySlug('nonexistent-slug');

        $this->assertNull($foundTenant);
    }

    public function test_it_finds_tenant_by_domain(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'unique.example.com']);

        $foundTenant = $this->tenantService->findByDomain('unique.example.com');

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
        $this->assertEquals('unique.example.com', $foundTenant->domain);
    }

    public function test_it_returns_null_when_tenant_not_found_by_domain(): void
    {
        $foundTenant = $this->tenantService->findByDomain('nonexistent.example.com');

        $this->assertNull($foundTenant);
    }

    public function test_it_gets_active_tenants(): void
    {
        Tenant::factory()->count(3)->create(['status' => 'active']);
        Tenant::factory()->count(2)->suspended()->create();
        Tenant::factory()->inactive()->create();

        $activeTenants = $this->tenantService->getActiveTenants();

        $this->assertCount(3, $activeTenants);
        $activeTenants->each(function ($tenant) {
            $this->assertEquals('active', $tenant->status);
        });
    }

    public function test_it_searches_tenants_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Acme Corporation']);
        Tenant::factory()->create(['name' => 'Tech Solutions Inc']);
        Tenant::factory()->create(['name' => 'Acme Industries']);

        $results = $this->tenantService->search('Acme');

        $this->assertCount(2, $results);
        $results->each(function ($tenant) {
            $this->assertStringContainsString('Acme', $tenant->name);
        });
    }

    public function test_it_searches_tenants_by_slug(): void
    {
        Tenant::factory()->create(['slug' => 'acme-corp']);
        Tenant::factory()->create(['slug' => 'tech-solutions']);
        Tenant::factory()->create(['slug' => 'acme-industries']);

        $results = $this->tenantService->search('acme');

        $this->assertCount(2, $results);
    }

    public function test_it_searches_tenants_by_domain(): void
    {
        Tenant::factory()->create(['domain' => 'acme.example.com']);
        Tenant::factory()->create(['domain' => 'tech.example.com']);
        Tenant::factory()->create(['domain' => 'acme-corp.example.com']);

        $results = $this->tenantService->search('acme');

        $this->assertCount(2, $results);
    }

    public function test_it_gets_paginated_tenants(): void
    {
        Tenant::factory()->count(25)->create();

        $paginated = $this->tenantService->getPaginated(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(25, $paginated->total());
        $this->assertCount(10, $paginated->items());
    }

    public function test_it_activates_tenant(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $activatedTenant = $this->tenantService->activate($tenant->id);

        $this->assertEquals('active', $activatedTenant->status);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'active',
        ]);
    }

    public function test_it_deactivates_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $deactivatedTenant = $this->tenantService->deactivate($tenant->id);

        $this->assertEquals('inactive', $deactivatedTenant->status);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'inactive',
        ]);
    }

    public function test_it_suspends_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $suspendedTenant = $this->tenantService->suspend($tenant->id);

        $this->assertEquals('suspended', $suspendedTenant->status);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'suspended',
        ]);
    }

    public function test_it_creates_tenant_with_custom_settings(): void
    {
        $data = [
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test-company.example.com',
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'currency' => 'USD',
            ],
        ];

        $tenant = $this->tenantService->create($data);

        $this->assertEquals('America/New_York', $tenant->settings['timezone']);
        $this->assertEquals('en', $tenant->settings['locale']);
        $this->assertEquals('USD', $tenant->settings['currency']);
    }

    public function test_it_creates_tenant_with_features(): void
    {
        $data = [
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test-company.example.com',
            'features' => ['sales', 'crm', 'inventory'],
        ];

        $tenant = $this->tenantService->create($data);

        $this->assertContains('sales', $tenant->features);
        $this->assertContains('crm', $tenant->features);
        $this->assertContains('inventory', $tenant->features);
    }

    public function test_it_creates_tenant_with_limits(): void
    {
        $data = [
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test-company.example.com',
            'limits' => [
                'users' => 10,
                'storage_mb' => 5120,
            ],
        ];

        $tenant = $this->tenantService->create($data);

        $this->assertEquals(10, $tenant->limits['users']);
        $this->assertEquals(5120, $tenant->limits['storage_mb']);
    }

    public function test_it_updates_tenant_settings(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'timezone' => 'UTC',
                'locale' => 'en',
            ],
        ]);

        $updatedTenant = $this->tenantService->update($tenant->id, [
            'settings' => [
                'timezone' => 'America/Los_Angeles',
                'locale' => 'es',
            ],
        ]);

        $this->assertEquals('America/Los_Angeles', $updatedTenant->settings['timezone']);
        $this->assertEquals('es', $updatedTenant->settings['locale']);
    }

    public function test_it_rolls_back_on_create_failure(): void
    {
        Event::fake();

        $this->expectException(\Exception::class);

        // Attempt to create tenant with duplicate slug
        $existingTenant = Tenant::factory()->create(['slug' => 'duplicate-slug']);

        try {
            $this->tenantService->create([
                'name' => 'Test',
                'slug' => 'duplicate-slug',
                'domain' => 'test.example.com',
            ]);
        } catch (\Exception $e) {
            // Verify the event was not dispatched on failure
            Event::assertNotDispatched(TenantCreated::class);
            throw $e;
        }
    }

    public function test_it_rolls_back_on_update_failure(): void
    {
        Event::fake();

        $tenant1 = Tenant::factory()->create(['slug' => 'tenant-1']);
        $tenant2 = Tenant::factory()->create(['slug' => 'tenant-2']);

        $this->expectException(\Exception::class);

        try {
            // Attempt to update with duplicate slug
            $this->tenantService->update($tenant1->id, [
                'slug' => 'tenant-2',
            ]);
        } catch (\Exception $e) {
            // Verify original data is preserved
            $tenant1->refresh();
            $this->assertEquals('tenant-1', $tenant1->slug);

            Event::assertNotDispatched(TenantUpdated::class);
            throw $e;
        }
    }
}
