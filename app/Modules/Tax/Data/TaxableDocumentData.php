<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxableDocumentData
{
    /**
     * @param list<TaxableDocumentLineData> $lines
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $documentType,
        public string $sourceModule,
        public string $sourceType,
        public int $sourceId,
        public string $sourceNumber,
        public string $sourceDate,
        public string $transactionDate,
        public ?string $partyType,
        public ?int $partyId,
        public array $lines,
    ) {}
}
