<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Entities\Tenant;
use Modules\Tenancy\Repositories\TenantRepository;
use Tests\TestCase;

class TenantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TenantRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(TenantRepository::class);
    }

    public function test_it_finds_tenant_by_id(): void
    {
        $tenant = Tenant::factory()->create();

        $found = $this->repository->findById($tenant->id);

        $this->assertNotNull($found);
        $this->assertEquals($tenant->id, $found->id);
        $this->assertEquals($tenant->name, $found->name);
    }

    public function test_it_returns_null_when_tenant_not_found_by_id(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    public function test_it_finds_tenant_by_slug(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'test-company']);

        $found = $this->repository->findBySlug('test-company');

        $this->assertNotNull($found);
        $this->assertEquals($tenant->id, $found->id);
        $this->assertEquals('test-company', $found->slug);
    }

    public function test_it_returns_null_when_tenant_not_found_by_slug(): void
    {
        $found = $this->repository->findBySlug('nonexistent-slug');

        $this->assertNull($found);
    }

    public function test_it_finds_tenant_by_domain(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'test.example.com']);

        $found = $this->repository->findByDomain('test.example.com');

        $this->assertNotNull($found);
        $this->assertEquals($tenant->id, $found->id);
        $this->assertEquals('test.example.com', $found->domain);
    }

    public function test_it_returns_null_when_tenant_not_found_by_domain(): void
    {
        $found = $this->repository->findByDomain('nonexistent.example.com');

        $this->assertNull($found);
    }

    public function test_it_creates_tenant(): void
    {
        $data = [
            'name' => 'New Tenant',
            'slug' => 'new-tenant',
            'domain' => 'new-tenant.example.com',
            'database' => 'tenant_new_tenant',
            'schema' => 'new_tenant',
            'status' => 'active',
        ];

        $tenant = $this->repository->create($data);

        $this->assertNotNull($tenant);
        $this->assertEquals('New Tenant', $tenant->name);
        $this->assertEquals('new-tenant', $tenant->slug);
        $this->assertDatabaseHas('tenants', ['slug' => 'new-tenant']);
    }

    public function test_it_updates_tenant(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Original Name']);

        $updated = $this->repository->update($tenant->id, [
            'name' => 'Updated Name',
            'status' => 'suspended',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('suspended', $updated->status);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_it_deletes_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->repository->delete($tenant->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_it_gets_all_tenants(): void
    {
        Tenant::factory()->count(5)->create();

        $tenants = $this->repository->all();

        $this->assertCount(5, $tenants);
    }

    public function test_it_paginates_tenants(): void
    {
        Tenant::factory()->count(25)->create();

        $paginated = $this->repository->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(25, $paginated->total());
        $this->assertCount(10, $paginated->items());
    }

    public function test_it_gets_active_tenants(): void
    {
        Tenant::factory()->count(3)->create(['status' => 'active']);
        Tenant::factory()->count(2)->suspended()->create();
        Tenant::factory()->inactive()->create();

        $activeTenants = $this->repository->getActiveTenants();

        $this->assertCount(3, $activeTenants);
        $activeTenants->each(function ($tenant) {
            $this->assertEquals('active', $tenant->status);
        });
    }

    public function test_it_searches_tenants_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Acme Corporation']);
        Tenant::factory()->create(['name' => 'Tech Solutions']);
        Tenant::factory()->create(['name' => 'Acme Industries']);

        $results = $this->repository->search('Acme');

        $this->assertCount(2, $results);
        $results->each(function ($tenant) {
            $this->assertStringContainsString('Acme', $tenant->name);
        });
    }

    public function test_it_searches_tenants_by_slug(): void
    {
        Tenant::factory()->create([
            'name' => 'Company A',
            'slug' => 'acme-corp',
        ]);
        Tenant::factory()->create([
            'name' => 'Company B',
            'slug' => 'tech-solutions',
        ]);
        Tenant::factory()->create([
            'name' => 'Company C',
            'slug' => 'acme-industries',
        ]);

        $results = $this->repository->search('acme');

        $this->assertCount(2, $results);
    }

    public function test_it_searches_tenants_by_domain(): void
    {
        Tenant::factory()->create([
            'name' => 'Company A',
            'domain' => 'acme-corp.example.com',
        ]);
        Tenant::factory()->create([
            'name' => 'Company B',
            'domain' => 'tech-solutions.example.com',
        ]);
        Tenant::factory()->create([
            'name' => 'Company C',
            'domain' => 'acme-industries.example.com',
        ]);

        $results = $this->repository->search('acme');

        $this->assertCount(2, $results);
    }

    public function test_it_handles_case_insensitive_search(): void
    {
        Tenant::factory()->create(['name' => 'ACME Corporation']);
        Tenant::factory()->create(['name' => 'acme industries']);

        $results = $this->repository->search('Acme');

        $this->assertCount(2, $results);
    }

    public function test_it_returns_empty_collection_when_search_has_no_results(): void
    {
        Tenant::factory()->count(3)->create();

        $results = $this->repository->search('NonexistentCompany');

        $this->assertCount(0, $results);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
    }

    public function test_it_finds_by_column(): void
    {
        $tenant = Tenant::factory()->create(['database' => 'tenant_special_db']);

        $found = $this->repository->findBy(['database' => 'tenant_special_db']);

        $this->assertNotNull($found);
        $this->assertEquals($tenant->id, $found->id);
    }

    public function test_it_finds_where_conditions_match(): void
    {
        Tenant::factory()->create(['status' => 'active', 'slug' => 'company-a']);
        Tenant::factory()->create(['status' => 'active', 'slug' => 'company-b']);
        Tenant::factory()->create(['status' => 'suspended', 'slug' => 'company-c']);

        $results = $this->repository->findWhere([
            'status' => 'active',
        ]);

        $this->assertCount(2, $results);
        $results->each(function ($tenant) {
            $this->assertEquals('active', $tenant->status);
        });
    }

    public function test_it_paginates_results_with_where_conditions(): void
    {
        Tenant::factory()->count(15)->create(['status' => 'active']);
        Tenant::factory()->count(5)->suspended()->create();

        $paginated = $this->repository->findWherePaginated(
            ['status' => 'active'],
            10
        );

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(15, $paginated->total());
        $this->assertCount(10, $paginated->items());
    }

    public function test_it_creates_tenant_with_complex_data(): void
    {
        $data = [
            'name' => 'Complex Tenant',
            'slug' => 'complex-tenant',
            'domain' => 'complex.example.com',
            'database' => 'tenant_complex',
            'schema' => 'complex',
            'status' => 'active',
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'currency' => 'USD',
            ],
            'features' => ['sales', 'crm', 'inventory'],
            'limits' => [
                'users' => 10,
                'storage_mb' => 5120,
            ],
            'trial_ends_at' => now()->addDays(14),
            'subscription_ends_at' => now()->addYear(),
        ];

        $tenant = $this->repository->create($data);

        $this->assertEquals('Complex Tenant', $tenant->name);
        $this->assertEquals('America/New_York', $tenant->settings['timezone']);
        $this->assertContains('sales', $tenant->features);
        $this->assertEquals(10, $tenant->limits['users']);
    }

    public function test_it_updates_tenant_with_complex_data(): void
    {
        $tenant = Tenant::factory()->create();

        $updateData = [
            'settings' => [
                'timezone' => 'Europe/London',
                'locale' => 'en-GB',
            ],
            'features' => ['sales', 'crm', 'accounting', 'reporting'],
            'limits' => [
                'users' => 50,
                'storage_mb' => 51200,
            ],
        ];

        $updated = $this->repository->update($tenant->id, $updateData);

        $this->assertEquals('Europe/London', $updated->settings['timezone']);
        $this->assertContains('accounting', $updated->features);
        $this->assertEquals(50, $updated->limits['users']);
    }
}
