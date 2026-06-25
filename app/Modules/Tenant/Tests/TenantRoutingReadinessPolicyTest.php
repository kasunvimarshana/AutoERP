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
    }
}
