<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\AuditLog;
use Modules\Core\Models\Tenant;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\TenantContext;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
        $this->auditService = app(AuditService::class);
    }

    public function test_can_log_model_event()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenant($tenant);

        $log = $this->auditService->log(
            $tenant,
            'created',
            [],
            $tenant->getAttributes()
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('created', $log->event);
        $this->assertEquals(Tenant::class, $log->auditable_type);
        $this->assertEquals($tenant->id, $log->auditable_id);
    }

    public function test_can_log_custom_event()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenantContext->setTenant($tenant);

        $log = $this->auditService->logCustomEvent(
            'custom_action',
            $tenant,
            ['action' => 'test'],
            ['tag1', 'tag2']
        );

        $this->assertEquals('custom_action', $log->event);
        $this->assertEquals(['action' => 'test'], $log->new_values);
        $this->assertEquals(['tag1', 'tag2'], $log->tags);
    }

    public function test_can_get_audit_trail()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->auditService->log($tenant, 'created', [], $tenant->getAttributes());
        $this->auditService->log($tenant, 'updated', [], $tenant->getAttributes());

        $trail = $this->auditService->getAuditTrail($tenant);

        $this->assertCount(2, $trail);
        $this->assertEquals('updated', $trail->first()->event);
        $this->assertEquals('created', $trail->last()->event);
    }
}
