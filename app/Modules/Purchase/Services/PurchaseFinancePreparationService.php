<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\JournalType;

final class PurchaseFinancePreparationService
{
    /**
     * Prepare finance journal DTOs only. Account mapping remains a later integration concern.
     *
     * @param  list<JournalLineData>  $lines
     */
    public function prepareJournal(
        int $tenantId,
        string $journalDate,
        string $sourceType,
        int $sourceId,
        array $lines,
        ?int $organizationUnitId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $description = null,
    ): CreateJournalEntryData {
        return new CreateJournalEntryData(
            tenantId: $tenantId,
            journalDate: $journalDate,
            journalType: JournalType::General,
            organizationUnitId: $organizationUnitId,
            source: new PostingSourceData($sourceType, $sourceId, $tenantId, $organizationUnitId),
            description: $description,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            lines: $lines,
        );
    }
}
