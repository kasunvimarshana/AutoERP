<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

use DateTimeImmutable;

final readonly class AuditQueryData
{
    public function __construct(
        public ?string $eventCategory,
        public ?string $eventName,
        public ?string $sourceModule,
        public ?string $actorType,
        public ?string $actorId,
        public ?string $subjectType,
        public ?string $subjectId,
        public ?DateTimeImmutable $fromUtc,
        public ?DateTimeImmutable $toUtcExclusive,
        public int $perPage,
        public ?string $cursor,
    ) {}
}
