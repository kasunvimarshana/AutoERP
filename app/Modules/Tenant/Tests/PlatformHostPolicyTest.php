<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Tenant\Services\Hosts\PlatformHostPolicy;
use Tests\TestCase;

final class PlatformHostPolicyTest extends TestCase
{
    public function test_configured_central_host_is_allowed_in_production(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');
        config()->set('tenant.resolution.central_hosts', ['Platform.Example.test.']);

        $policy = new PlatformHostPolicy($this->app);

        self::assertTrue($policy->isCentralHost('platform.example.test'));
        self::assertFalse($policy->isCentralHost('tenant.example.test'));
    }

    public function test_loopback_hosts_are_rejected_in_production(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');
        config()->set('tenant.resolution.central_hosts', []);

        $policy = new PlatformHostPolicy($this->app);

        self::assertFalse($policy->isCentralHost('localhost'));
        self::assertFalse($policy->isCentralHost('127.0.0.1'));
        self::assertFalse($policy->isCentralHost('::1'));
    }

    public function test_loopback_hosts_are_allowed_only_for_local_development(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'local');
        config()->set('tenant.resolution.central_hosts', []);

        $policy = new PlatformHostPolicy($this->app);

        self::assertTrue($policy->isCentralHost('localhost'));
        self::assertTrue($policy->isCentralHost('127.0.0.1'));
    }
}
