<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxReturnDocumentData
{
    /**
     * @param list<TaxReturnLineData> $lines
     * @param array<string, mixed> $noteMetadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $sourceModule,
        public string $sourceType,
        public int $sourceId,
        public string $sourceNumber,
        public string $sourceDate,
        public ?string $partyType,
        public ?int $partyId,
        public array $lines,
        public array $noteMetadata = [],
    ) {}
}
