<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

final readonly class ConfigurationGlobalImpact
{
    public function __construct(
        public string $key,
        public int $tenantCount,
        public int $tenantOverrideCount,
        public int $inheritingTenantCount,
    ) {}
}
