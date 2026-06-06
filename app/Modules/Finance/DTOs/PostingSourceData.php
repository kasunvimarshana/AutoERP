<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class PostingSourceData
{
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
    ) {}
}
