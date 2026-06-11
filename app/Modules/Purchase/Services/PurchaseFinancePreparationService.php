<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingSourceData;

final class PurchaseFinancePreparationService
{
    public function __construct(private readonly FinancePostingInterface $financePostings) {}

    /**
     * @param  list<FinancePostingLine>  $lines
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
        ?string $sourceNumber = null,
        ?string $sourceDate = null,
        ?string $postingProfileCode = null,
    ): FinancePostingRequest {
        return new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: $sourceType,
                sourceId: $sourceId,
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
                sourceModule: 'purchase',
                sourceNumber: $sourceNumber,
                sourceDate: $sourceDate ?? $journalDate,
            ),
            postingDate: $journalDate,
            description: $description,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            lines: $lines,
            postingProfileCode: $postingProfileCode,
        );
    }

    public function validatePostingRequest(FinancePostingRequest $request): void
    {
        $this->financePostings->validatePosting($request);
    }
}
