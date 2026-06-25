<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

use DateTimeImmutable;

final readonly class ConfigurationRevisionView
{
    public function __construct(
        public int $id,
        public string $operation,
        public string $scope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public string $key,
        public int $definitionVersion,
        public bool $definitionCompatible,
        public mixed $value,
        public bool $configured,
        public bool $sensitive,
        public ?int $resultingRowVersion,
        public ?int $sourceRevisionId,
        public ?int $actorUserId,
        public ?string $actorName,
        public ?string $reason,
        public DateTimeImmutable $createdAt,
    ) {}
}
