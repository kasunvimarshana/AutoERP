<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Data;

final readonly class ConfigurationGlobalImpact
{
    public function __construct(
        public string $key,
        public int $tenantCount,
        public int $tenantOverrideCount,
        public int $inheritingTenantCount,
        public int $organizationUnitCount,
        public int $organizationUnitOverrideCount,
        public int $organizationUnitWithoutDirectOverrideCount,
    ) {}
}
