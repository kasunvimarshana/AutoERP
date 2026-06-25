<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

use DateTimeImmutable;

final readonly class StoredConfigurationValue
{
    public function __construct(
        public int $id,
        public string $scope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public string $key,
        public int $definitionVersion,
        public ?string $storedValue,
        public string $valueType,
        public bool $sensitive,
        public int $rowVersion,
        public DateTimeImmutable $updatedAt,
    ) {}
}
