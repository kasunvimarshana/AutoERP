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

use DateTimeImmutable;

final readonly class ConfigurationEntryView
{
    public function __construct(
        public ConfigurationDefinition $definition,
        public string $scope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public mixed $value,
        public mixed $inheritedValue,
        public bool $inheritedConfigured,
        public string $inheritedSourceScope,
        public bool $inheritedUsesDefault,
        public int $rowVersion,
        public DateTimeImmutable $updatedAt,
    ) {}
}
