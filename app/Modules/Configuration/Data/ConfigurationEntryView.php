<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

use DateTimeImmutable;

final readonly class ConfigurationEntryView
{
    public function __construct(
        public ConfigurationDefinition $definition,
        public string $scope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public mixed $value,
        public int $rowVersion,
        public DateTimeImmutable $updatedAt,
    ) {}
}
