<?php

declare(strict_types=1);

namespace Modules\Core\DTOs\Integration;

final readonly class PostingSourceData
{
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public int $tenantId,
        public ?int $organizationUnitId = null,
    ) {}
}
