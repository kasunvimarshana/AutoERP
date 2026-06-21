<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

use DateTimeImmutable;

final readonly class AuditEventData
{
    /**
     * @param  array<string, mixed>|null  $changes
     * @param  array<string, mixed>|null  $metadata
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $eventName,
        public string $eventCategory,
        public string $sourceModule,
        public string $subjectType,
        public string $subjectId,
        public ?string $subjectReference = null,
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        public ?string $sourceReference = null,
        public ?array $changes = null,
        public ?array $metadata = null,
        public array $tags = [],
        public ?DateTimeImmutable $occurredAt = null,
        public ?string $producerKey = null,
    ) {}
}
