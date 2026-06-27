<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Tenant\Services\Domains\TenantDomainReadinessPolicy;
use Modules\Tenant\Services\Domains\TenantRoutingReadinessPolicy;
use Tests\TestCase;

final class TenantRoutingReadinessPolicyTest extends TestCase
{
    public function test_local_fallback_can_satisfy_local_activation_without_storing_localhost_as_a_domain(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'local');
        config()->set('tenant.resolution.local_fallback_enabled', true);
        config()->set('tenant.resolution.local_fallback_tenant_code', 'AUTOERP');

        $result = (new TenantRoutingReadinessPolicy(
            $this->app,
            new TenantDomainReadinessPolicy(),
        ))->inspect('autoerp', null);

        self::assertTrue($result['ready']);
        self::assertSame(TenantRoutingReadinessPolicy::MODE_LOCAL_FALLBACK, $result['mode']);
        self::assertSame([
            'supported' => true,
            'enabled' => true,
            'configured_tenant_code' => 'AUTOERP',
            'matches_tenant' => true,
        ], $result['local_fallback']);
    }

    public function test_local_fallback_never_weakens_production_domain_readiness(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');
        config()->set('tenant.resolution.local_fallback_enabled', true);
        config()->set('tenant.resolution.local_fallback_tenant_code', 'AUTOERP');

        $result = (new TenantRoutingReadinessPolicy(
            $this->app,
            new TenantDomainReadinessPolicy(),
        ))->inspect('AUTOERP', null);

        self::assertFalse($result['ready']);
        self::assertSame(TenantRoutingReadinessPolicy::MODE_UNAVAILABLE, $result['mode']);
        self::assertSame([
            'supported' => false,
            'enabled' => false,
            'configured_tenant_code' => null,
            'matches_tenant' => false,
        ], $result['local_fallback']);
    }

    public function test_local_fallback_reports_a_tenant_code_mismatch_for_actionable_readiness_guidance(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'local');
        config()->set('tenant.resolution.local_fallback_enabled', true);
        config()->set('tenant.resolution.local_fallback_tenant_code', 'OTHER');

        $result = (new TenantRoutingReadinessPolicy(
            $this->app,
            new TenantDomainReadinessPolicy(),
        ))->inspect('AUTOERP', null);

        self::assertFalse($result['ready']);
        self::assertSame('OTHER', $result['local_fallback']['configured_tenant_code']);
        self::assertFalse($result['local_fallback']['matches_tenant']);
    }
}
