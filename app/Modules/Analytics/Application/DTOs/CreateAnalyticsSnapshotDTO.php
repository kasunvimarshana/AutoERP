<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

readonly class CreateAnalyticsSnapshotDTO
{
    public function __construct(
        public int $tenantId,
        public ?int $orgUnitId,
        public string $summaryDate,
        public ?array $metadata,
    ) {}
}
