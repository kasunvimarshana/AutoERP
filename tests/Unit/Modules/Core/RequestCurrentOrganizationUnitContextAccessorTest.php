<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Core;

use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Application\DTO\CurrentOrganizationUnitContext;
use Modules\Core\Infrastructure\Support\RequestCurrentOrganizationUnitContextAccessor;
use PHPUnit\Framework\TestCase;

final class RequestCurrentOrganizationUnitContextAccessorTest extends TestCase
{
    public function testItRebuildsCurrentOrganizationUnitContextFromRequestAttributes(): void
    {
        $request = Request::create('/api/example', 'GET');
        $request->attributes->set('current_organization_unit', [
            'organization_unit' => [
                'id' => 9,
                'tenant_id' => 7,
                'name' => 'Head Office',
                'code' => 'HO',
                'path' => '/root/ho',
                'is_active' => true,
            ],
            'organization_unit_id' => 9,
            'tenant_id' => 7,
            'organization_unit_name' => 'Head Office',
            'organization_unit_code' => 'HO',
            'organization_unit_path' => '/root/ho',
            'is_active' => true,
            'application_id' => 'erp-web',
            'source' => 'request_metadata',
        ]);

        $accessor = new RequestCurrentOrganizationUnitContextAccessor(
            $request,
            'current_organization_unit',
            'current_organization_unit_id',
            'current_organization_unit_tenant_id',
            'current_organization_unit_code',
            'current_organization_unit_path',
            'current_organization_unit_name',
            'current_organization_unit_is_active',
            'current_application_id',
            'current_organization_unit_source',
        );

        $context = $accessor->current();

        self::assertInstanceOf(CurrentOrganizationUnitContext::class, $context);
        self::assertSame(9, $context->organizationUnitId());
        self::assertSame(7, $context->tenantId());
        self::assertSame('HO', $context->code());
        self::assertSame('/root/ho', $context->path());
        self::assertSame('Head Office', $context->name());
        self::assertTrue($context->isActive());
        self::assertSame('erp-web', $context->applicationId());
        self::assertSame('request_metadata', $context->source());
        self::assertSame(9, $accessor->currentOrganizationUnitId());
        self::assertSame(7, $accessor->currentTenantId());
    }

    public function testItFallsBackToIndividualRequestAttributesWhenSerializedContextIsMissing(): void
    {
        $request = Request::create('/api/example', 'GET');
        $request->attributes->set('current_organization_unit_id', 11);
        $request->attributes->set('current_organization_unit_tenant_id', 7);
        $request->attributes->set('current_organization_unit_name', 'Branch 01');
        $request->attributes->set('current_organization_unit_code', 'B01');
        $request->attributes->set('current_organization_unit_path', '/root/b01');
        $request->attributes->set('current_organization_unit_is_active', true);
        $request->attributes->set('current_application_id', 'backoffice');
        $request->attributes->set('current_organization_unit_source', 'authenticated_user');

        $accessor = new RequestCurrentOrganizationUnitContextAccessor(
            $request,
            'current_organization_unit',
            'current_organization_unit_id',
            'current_organization_unit_tenant_id',
            'current_organization_unit_code',
            'current_organization_unit_path',
            'current_organization_unit_name',
            'current_organization_unit_is_active',
            'current_application_id',
            'current_organization_unit_source',
        );

        $context = $accessor->requireCurrent();

        self::assertSame(11, $context->organizationUnitId());
        self::assertSame(7, $context->tenantId());
        self::assertSame('B01', $context->code());
        self::assertSame('/root/b01', $context->path());
        self::assertSame('Branch 01', $context->name());
        self::assertSame('backoffice', $context->applicationId());
        self::assertSame('authenticated_user', $context->source());
    }

    public function testItThrowsWhenCurrentOrganizationUnitContextIsUnavailable(): void
    {
        $request = Request::create('/api/example', 'GET');

        $accessor = new RequestCurrentOrganizationUnitContextAccessor(
            $request,
            'current_organization_unit',
            'current_organization_unit_id',
            'current_organization_unit_tenant_id',
            'current_organization_unit_code',
            'current_organization_unit_path',
            'current_organization_unit_name',
            'current_organization_unit_is_active',
            'current_application_id',
            'current_organization_unit_source',
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Current organization unit context is not available on the active request.');

        $accessor->requireCurrent();
    }
}
