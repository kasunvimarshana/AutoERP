<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

use Carbon\CarbonImmutable;
use DateTimeInterface;

interface TimezoneResolverInterface
{
    /**
     * Resolves the canonical IANA timezone for the given tenant/organization unit,
     * or active execution context, falling back to application configured timezone or UTC.
     */
    public function resolve(?int $tenantId = null, ?int $organizationUnitId = null): string;

    /**
     * Returns current instant in the resolved timezone.
     */
    public function now(?int $tenantId = null, ?int $organizationUnitId = null): CarbonImmutable;

    /**
     * Returns today's civil date at start-of-day in the resolved timezone.
     */
    public function today(?int $tenantId = null, ?int $organizationUnitId = null): CarbonImmutable;

    /**
     * Returns today's civil date string (Y-m-d) in the resolved timezone.
     */
    public function todayDateString(?int $tenantId = null, ?int $organizationUnitId = null): string;

    /**
     * Converts a given instant or datetime string to a civil date string (Y-m-d) in the resolved timezone.
     */
    public function toCivilDate(DateTimeInterface|string $instant, ?int $tenantId = null, ?int $organizationUnitId = null): string;

    /**
     * Converts a given instant or datetime string to CarbonImmutable in the resolved timezone.
     */
    public function toCivilDateTime(DateTimeInterface|string $instant, ?int $tenantId = null, ?int $organizationUnitId = null): CarbonImmutable;
}
