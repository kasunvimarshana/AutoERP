<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Tenant;
use Modules\Core\Services\CacheService;
use Modules\Core\Services\TenantContext;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CacheService $cacheService;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
        $this->cacheService = app(CacheService::class);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_cache_uses_tenant_prefix()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenant($tenant);

        $this->cacheService->put('test_key', 'test_value', 60);

        $this->assertEquals('test_value', $this->cacheService->get('test_key'));
        $this->assertTrue($this->cacheService->has('test_key'));
    }

    public function test_cache_isolation_between_tenants()
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
        $this->cacheService->put('key', 'value1', 60);

        $this->tenantContext->setTenant($tenant2);
        $this->cacheService->put('key', 'value2', 60);

        $this->assertEquals('value2', $this->cacheService->get('key'));

        $this->tenantContext->setTenant($tenant1);
        $this->assertEquals('value1', $this->cacheService->get('key'));
    }

    public function test_can_remember_value()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenant($tenant);

        $callCount = 0;
        $value = $this->cacheService->remember('test_key', 60, function () use (&$callCount) {
            $callCount++;

            return 'computed_value';
        });

        $this->assertEquals('computed_value', $value);
        $this->assertEquals(1, $callCount);

        // Second call should not execute callback
        $value = $this->cacheService->remember('test_key', 60, function () use (&$callCount) {
            $callCount++;

            return 'computed_value';
        });

        $this->assertEquals('computed_value', $value);
        $this->assertEquals(1, $callCount);
    }

    public function test_can_forget_value()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenant($tenant);

        $this->cacheService->put('test_key', 'test_value', 60);
        $this->assertTrue($this->cacheService->has('test_key'));

        $this->cacheService->forget('test_key');
        $this->assertFalse($this->cacheService->has('test_key'));
    }
}
