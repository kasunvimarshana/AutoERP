<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Tests;

use Carbon\CarbonImmutable;
use Modules\Configuration\Constants\ConfigurationKey;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\Contracts\TimezoneResolverInterface;
use Tests\TestCase;

final class TimezoneResolverTest extends TestCase
{
    public function test_resolves_configured_tenant_timezone(): void
    {
        $mockConfig = $this->createMock(ConfigurationResolverInterface::class);
        $mockConfig->expects($this->once())
            ->method('value')
            ->with(ConfigurationKey::WORKSPACE_TIMEZONE, 42, null)
            ->willReturn('America/New_York');

        $this->app->instance(ConfigurationResolverInterface::class, $mockConfig);

        /** @var TimezoneResolverInterface $resolver */
        $resolver = $this->app->make(TimezoneResolverInterface::class);

        $this->assertSame('America/New_York', $resolver->resolve(42));
    }

    public function test_resolves_organization_unit_timezone(): void
    {
        $mockConfig = $this->createMock(ConfigurationResolverInterface::class);
        $mockConfig->expects($this->once())
            ->method('value')
            ->with(ConfigurationKey::WORKSPACE_TIMEZONE, 42, 99)
            ->willReturn('Asia/Tokyo');

        $this->app->instance(ConfigurationResolverInterface::class, $mockConfig);

        /** @var TimezoneResolverInterface $resolver */
        $resolver = $this->app->make(TimezoneResolverInterface::class);

        $this->assertSame('Asia/Tokyo', $resolver->resolve(42, 99));
    }

    public function test_falls_back_to_app_timezone_when_unconfigured_or_invalid(): void
    {
        config()->set('app.timezone', 'Europe/London');

        $mockConfig = $this->createMock(ConfigurationResolverInterface::class);
        $mockConfig->method('value')->willReturn('Invalid/Timezone');

        $this->app->instance(ConfigurationResolverInterface::class, $mockConfig);

        /** @var TimezoneResolverInterface $resolver */
        $resolver = $this->app->make(TimezoneResolverInterface::class);

        $this->assertSame('Europe/London', $resolver->resolve(42));
    }

    public function test_falls_back_to_utc_when_app_timezone_is_invalid(): void
    {
        config()->set('app.timezone', 'Bad/Tz');

        $mockConfig = $this->createMock(ConfigurationResolverInterface::class);
        $mockConfig->method('value')->willReturn('');

        $this->app->instance(ConfigurationResolverInterface::class, $mockConfig);

        /** @var TimezoneResolverInterface $resolver */
        $resolver = $this->app->make(TimezoneResolverInterface::class);

        $this->assertSame('UTC', $resolver->resolve(42));
    }

    public function test_to_civil_date_converts_utc_instant_correctly(): void
    {
        $mockConfig = $this->createMock(ConfigurationResolverInterface::class);
        $mockConfig->method('value')->willReturn('Asia/Colombo'); // +05:30

        $this->app->instance(ConfigurationResolverInterface::class, $mockConfig);

        /** @var TimezoneResolverInterface $resolver */
        $resolver = $this->app->make(TimezoneResolverInterface::class);

        // 2026-11-23 20:00:00 UTC is 2026-11-24 01:30:00 in +05:30
        $utcInstant = CarbonImmutable::parse('2026-11-23 20:00:00', 'UTC');

        $this->assertSame('2026-11-24', $resolver->toCivilDate($utcInstant, 42));

        // When given a pure civil date string, it preserves it
        $this->assertSame('2026-11-23', $resolver->toCivilDate('2026-11-23', 42));
    }

    public function test_today_date_string_reflects_configured_timezone(): void
    {
        $mockConfig = $this->createMock(ConfigurationResolverInterface::class);
        $mockConfig->method('value')->willReturn('Pacific/Auckland');

        $this->app->instance(ConfigurationResolverInterface::class, $mockConfig);

        /** @var TimezoneResolverInterface $resolver */
        $resolver = $this->app->make(TimezoneResolverInterface::class);

        $expected = CarbonImmutable::now('Pacific/Auckland')->toDateString();
        $this->assertSame($expected, $resolver->todayDateString(42));
    }
}
