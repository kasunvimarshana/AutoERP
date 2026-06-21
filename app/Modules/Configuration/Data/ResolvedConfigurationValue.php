<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

final readonly class ResolvedConfigurationValue
{
    public function __construct(
        public ConfigurationDefinition $definition,
        public mixed $value,
        public string $sourceScope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public ?int $rowVersion,
        public bool $usesDefault,
    ) {}
}
