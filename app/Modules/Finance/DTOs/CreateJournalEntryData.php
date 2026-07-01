<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;

final readonly class CreateJournalEntryData
{
    /**
     * @param  list<JournalLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $journalDate,
        public JournalType $journalType = JournalType::General,
        public ?int $organizationUnitId = null,
        public ?string $journalNumber = null,
        public ?PostingSourceData $source = null,
        public JournalStatus $status = JournalStatus::Draft,
        public ?string $description = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?int $createdBy = null,
        public array $lines = [],
        public ?int $postingProfileId = null,
        public ?int $reversalOfId = null,
        public ?string $reversalReason = null,
        public ?string $sourceKey = null,
        public ?string $postingFingerprint = null,
    ) {}
}
