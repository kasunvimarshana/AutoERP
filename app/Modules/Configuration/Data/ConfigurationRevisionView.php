<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

use DateTimeImmutable;

final readonly class ConfigurationRevisionView
{
    public function __construct(
        public int $id,
        public string $scope,
        public string $key,
        public string $action,
        public string $valueType,
        public bool $sensitive,
        public bool $beforeExists,
        public mixed $beforeValue,
        public bool $afterExists,
        public mixed $afterValue,
        public int $entryRowVersion,
        public ?int $changedBy,
        public ?string $changedByName,
        public DateTimeImmutable $createdAt,
    ) {}
}
