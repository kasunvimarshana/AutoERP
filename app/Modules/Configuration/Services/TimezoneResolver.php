<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\Container\Container;
use Modules\Configuration\Constants\ConfigurationKey;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TimezoneResolverInterface;

final class TimezoneResolver implements TimezoneResolverInterface
{
    private const FALLBACK_TIMEZONE = 'UTC';

    public function __construct(
        private readonly Container $container,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly CurrentOrganizationUnitContextAccessorInterface $organizationUnitContext,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function resolve(?int $tenantId = null, ?int $organizationUnitId = null): string
    {
        $tenantId ??= $this->resolveTenantId();
        $organizationUnitId ??= $this->resolveOrganizationUnitId();

        if ($tenantId !== null && $tenantId > 0) {
            $configured = $this->resolveFromConfiguration($tenantId, $organizationUnitId);
            if ($configured !== null) {
                return $configured;
            }
        }

        $appTimezone = (string) config('app.timezone', self::FALLBACK_TIMEZONE);
        if ($this->isValidTimezone($appTimezone)) {
            return $appTimezone;
        }

        return self::FALLBACK_TIMEZONE;
    }

    public function now(?int $tenantId = null, ?int $organizationUnitId = null): CarbonImmutable
    {
        return CarbonImmutable::now($this->resolve($tenantId, $organizationUnitId));
    }

    public function today(?int $tenantId = null, ?int $organizationUnitId = null): CarbonImmutable
    {
        return $this->now($tenantId, $organizationUnitId)->startOfDay();
    }

    public function todayDateString(?int $tenantId = null, ?int $organizationUnitId = null): string
    {
        return $this->today($tenantId, $organizationUnitId)->toDateString();
    }

    public function toCivilDate(DateTimeInterface|string $instant, ?int $tenantId = null, ?int $organizationUnitId = null): string
    {
        $tz = $this->resolve($tenantId, $organizationUnitId);
        $carbon = $instant instanceof DateTimeInterface
            ? CarbonImmutable::instance($instant)
            : CarbonImmutable::parse($instant);

        return $carbon->setTimezone($tz)->toDateString();
    }

    public function toCivilDateTime(DateTimeInterface|string $instant, ?int $tenantId = null, ?int $organizationUnitId = null): CarbonImmutable
    {
        $tz = $this->resolve($tenantId, $organizationUnitId);
        $carbon = $instant instanceof DateTimeInterface
            ? CarbonImmutable::instance($instant)
            : CarbonImmutable::parse($instant);

        return $carbon->setTimezone($tz);
    }

    private function resolveTenantId(): ?int
    {
        $id = $this->tenantContext->currentTenantId();
        if ($id !== null && $id > 0) {
            return $id;
        }

        return $this->executionContext->tenantId();
    }

    private function resolveOrganizationUnitId(): ?int
    {
        $id = $this->organizationUnitContext->currentOrganizationUnitId();
        if ($id !== null && $id > 0) {
            return $id;
        }

        return null;
    }

    private function resolveFromConfiguration(int $tenantId, ?int $organizationUnitId): ?string
    {
        try {
            if (! $this->container->bound(ConfigurationResolverInterface::class)) {
                return null;
            }

            /** @var ConfigurationResolverInterface $resolver */
            $resolver = $this->container->make(ConfigurationResolverInterface::class);
            $value = $resolver->value(ConfigurationKey::WORKSPACE_TIMEZONE, $tenantId, $organizationUnitId);

            if (is_string($value) && $this->isValidTimezone($value)) {
                return $value;
            }
        } catch (\Throwable) {
            // Configuration repository not yet ready or table missing during migration
            return null;
        }

        return null;
    }

    private function isValidTimezone(string $timezone): bool
    {
        if (trim($timezone) === '') {
            return false;
        }

        return in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }
}
