<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

final readonly class ConfigurationScopeContext
{
    public function __construct(
        public string $scope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
    ) {}
}
