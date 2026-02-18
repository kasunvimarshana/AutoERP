<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Tenant;
use Modules\Core\Services\TenantContext;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
    }

    public function test_can_set_and_get_tenant()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenant($tenant);

        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertEquals($tenant->id, $this->tenantContext->getTenantId());
        $this->assertEquals($tenant->id, $this->tenantContext->getTenant()->id);
    }

    public function test_can_clear_tenant()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenant($tenant);
        $this->assertTrue($this->tenantContext->hasTenant());

        $this->tenantContext->clear();
        $this->assertFalse($this->tenantContext->hasTenant());
        $this->assertNull($this->tenantContext->getTenantId());
    }

    public function test_can_set_tenant_by_id()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenantById($tenant->id);

        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertEquals($tenant->id, $this->tenantContext->getTenantId());
    }

    public function test_run_for_tenant()
    {
        $tenant1 = Tenant::create([
            'name' => 'Tenant 1',
            'domain' => 'tenant1.example.com',
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Tenant 2',
            'domain' => 'tenant2.example.com',
        ]);

        $this->tenantContext->setTenant($tenant1);
        $this->assertEquals($tenant1->id, $this->tenantContext->getTenantId());

        $result = $this->tenantContext->runForTenant($tenant2, function () {
            return $this->tenantContext->getTenantId();
        });

        $this->assertEquals($tenant2->id, $result);
        $this->assertEquals($tenant1->id, $this->tenantContext->getTenantId());
    }
}
