<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Core;

use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Application\DTO\CurrentTenantContext;
use Modules\Core\Infrastructure\Support\RequestCurrentTenantContextAccessor;
use PHPUnit\Framework\TestCase;

final class RequestCurrentTenantContextAccessorTest extends TestCase
{
    public function testItRebuildsCurrentTenantContextFromRequestAttributes(): void
    {
        $request = Request::create('/api/example', 'GET');
        $request->attributes->set('current_tenant', [
            'tenant' => [
                'id' => 11,
                'code' => 'TENANT-11',
                'uuid' => '00000000-0000-0000-0000-000000000011',
                'isolation_key' => 'iso-11',
                'status' => 'active',
                'is_active' => true,
            ],
            'tenant_id' => 11,
            'tenant_code' => 'TENANT-11',
            'tenant_uuid' => '00000000-0000-0000-0000-000000000011',
            'isolation_key' => 'iso-11',
            'domain' => 'tenant11.example.com',
            'status' => 'active',
            'is_active' => true,
            'application_id' => 'erp-web',
            'source' => 'request_metadata',
        ]);

        $accessor = new RequestCurrentTenantContextAccessor(
            $request,
            'current_tenant',
            'current_tenant_id',
            'current_tenant_code',
            'current_tenant_uuid',
            'current_tenant_isolation_key',
            'current_tenant_domain',
            'current_tenant_status',
            'current_tenant_is_active',
            'current_application_id',
            'current_tenant_source',
        );

        $context = $accessor->current();

        self::assertInstanceOf(CurrentTenantContext::class, $context);
        self::assertSame(11, $context->tenantId());
        self::assertSame('TENANT-11', $context->tenantCode());
        self::assertSame('00000000-0000-0000-0000-000000000011', $context->tenantUuid());
        self::assertSame('iso-11', $context->isolationKey());
        self::assertSame('tenant11.example.com', $context->domain());
        self::assertSame('active', $context->status());
        self::assertTrue($context->isActive());
        self::assertSame('erp-web', $context->applicationId());
        self::assertSame('request_metadata', $context->source());
        self::assertSame(11, $accessor->currentTenantId());
        self::assertSame('TENANT-11', $accessor->currentTenantCode());
        self::assertSame('tenant11.example.com', $accessor->currentTenantDomain());
        self::assertSame('erp-web', $accessor->currentApplicationId());
    }

    public function testItFallsBackToIndividualTenantAttributes(): void
    {
        $request = Request::create('/api/example', 'GET');
        $request->attributes->set('current_tenant_id', 21);
        $request->attributes->set('current_tenant_code', 'TENANT-21');
        $request->attributes->set('current_tenant_uuid', '00000000-0000-0000-0000-000000000021');
        $request->attributes->set('current_tenant_isolation_key', 'iso-21');
        $request->attributes->set('current_tenant_domain', 'tenant21.example.com');
        $request->attributes->set('current_tenant_status', 'active');
        $request->attributes->set('current_tenant_is_active', true);
        $request->attributes->set('current_application_id', 'backoffice');
        $request->attributes->set('current_tenant_source', 'authenticated_user');

        $accessor = new RequestCurrentTenantContextAccessor(
            $request,
            'current_tenant',
            'current_tenant_id',
            'current_tenant_code',
            'current_tenant_uuid',
            'current_tenant_isolation_key',
            'current_tenant_domain',
            'current_tenant_status',
            'current_tenant_is_active',
            'current_application_id',
            'current_tenant_source',
        );

        $context = $accessor->requireCurrent();

        self::assertSame(21, $context->tenantId());
        self::assertSame('TENANT-21', $context->tenantCode());
        self::assertSame('00000000-0000-0000-0000-000000000021', $context->tenantUuid());
        self::assertSame('tenant21.example.com', $context->domain());
        self::assertSame('backoffice', $context->applicationId());
        self::assertSame('authenticated_user', $context->source());
    }

    public function testItThrowsWhenCurrentTenantContextIsUnavailable(): void
    {
        $request = Request::create('/api/example', 'GET');

        $accessor = new RequestCurrentTenantContextAccessor(
            $request,
            'current_tenant',
            'current_tenant_id',
            'current_tenant_code',
            'current_tenant_uuid',
            'current_tenant_isolation_key',
            'current_tenant_domain',
            'current_tenant_status',
            'current_tenant_is_active',
            'current_application_id',
            'current_tenant_source',
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Current tenant context is not available on the active request.');

        $accessor->requireCurrent();
    }
}
