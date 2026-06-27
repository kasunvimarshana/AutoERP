<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class PostingSourceData
{
    public const DEFAULT_POSTING_KEY = 'primary';

    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
        public ?string $sourceModule = null,
        public ?string $sourceNumber = null,
        public ?string $sourceDate = null,
        public string $postingKey = self::DEFAULT_POSTING_KEY,
    ) {}
}
