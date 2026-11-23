<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Modules\Core\Contracts\TimezoneResolverInterface;

final class DefaultTimezoneResolver implements TimezoneResolverInterface
{
    private const FALLBACK_TIMEZONE = 'UTC';

    public function resolve(?int $tenantId = null, ?int $organizationUnitId = null): string
    {
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

    private function isValidTimezone(string $timezone): bool
    {
        if (trim($timezone) === '') {
            return false;
        }

        return in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }
}
