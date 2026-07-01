<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxPostingContext;
use Modules\Tax\Models\TaxPostingProfile;

final class TaxPostingContextService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  list<TaxAmountData>  $taxLines
     */
    public function build(
        PostingSourceData $source,
        string $postingDate,
        array $taxLines,
        string $postingProfileCode,
        string $counterpartyProfileKey,
        string $counterpartyLineName,
        ?string $description = null,
    ): TaxPostingContext {
        if ($source->tenantId === null) {
            throw new InvalidArgumentException('Tax posting source tenant is required.');
        }
        $postingProfileCode = trim($postingProfileCode);
        $counterpartyProfileKey = trim($counterpartyProfileKey);
        if ($postingProfileCode === '') {
            throw new InvalidArgumentException('Tax posting context requires a Finance posting profile code.');
        }
        if ($counterpartyProfileKey === '') {
            throw new InvalidArgumentException('Tax posting context requires a counterparty posting profile key.');
        }

        $financeLines = [];
        foreach ($taxLines as $taxLine) {
            if (! $taxLine instanceof TaxAmountData || $this->math->isZero($taxLine->taxAmount)) {
                continue;
            }

            $profile = $this->resolveProfile(
                (int) $source->tenantId,
                $source->organizationUnitId,
                $taxLine,
            );
            $taxProfileKey = trim((string) $profile->posting_key);
            $lineDescription = $taxLine->taxCode.' '.$taxLine->taxName;
            $counterparty = new PostingLine(
                accountName: $counterpartyLineName,
                debit: $this->taxCreditsAccount($taxLine) ? $taxLine->taxAmount : '0.000000',
                credit: $this->taxCreditsAccount($taxLine) ? '0.000000' : $taxLine->taxAmount,
                description: $lineDescription.' counterparty',
                profileKey: $counterpartyProfileKey,
                sourceLineType: 'tax',
                sourceLineId: $taxLine->taxId,
            );
            $taxPostingLine = new PostingLine(
                accountName: $lineDescription,
                debit: $this->taxCreditsAccount($taxLine) ? '0.000000' : $taxLine->taxAmount,
                credit: $this->taxCreditsAccount($taxLine) ? $taxLine->taxAmount : '0.000000',
                description: $lineDescription,
                profileKey: $taxProfileKey,
                sourceLineType: 'tax',
                sourceLineId: $taxLine->taxId,
            );

            $financeLines[] = $counterparty;
            $financeLines[] = $taxPostingLine;
        }

        return new TaxPostingContext(
            source: $source,
            postingDate: $postingDate,
            taxLines: $taxLines,
            financeContext: new PostingContext(
                source: $source,
                postingDate: $postingDate,
                lines: $financeLines,
                description: $description,
                postingProfileCode: $postingProfileCode,
            ),
        );
    }

    private function resolveProfile(int $tenantId, ?int $organizationUnitId, TaxAmountData $taxLine): TaxPostingProfile
    {
        $direction = $this->directionFor($taxLine);
        $query = TaxPostingProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('tax_id', $taxLine->taxId)
            ->where('direction', $direction)
            ->where('active', true);
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $profile = $query->first();
        if (! $profile instanceof TaxPostingProfile) {
            throw new InvalidArgumentException("Tax posting profile is missing for tax [{$taxLine->taxCode}] direction [{$direction}].");
        }
        if (trim((string) $profile->posting_key) === '') {
            throw new InvalidArgumentException("Tax posting profile for tax [{$taxLine->taxCode}] direction [{$direction}] must define a Finance posting key.");
        }

        return $profile;
    }

    private function directionFor(TaxAmountData $taxLine): string
    {
        if ($taxLine->isWithholding) {
            return 'withholding';
        }
        if ($taxLine->receivable || $taxLine->recoverable) {
            return 'input';
        }
        if ($taxLine->payable) {
            return 'output';
        }

        return 'tax';
    }

    private function taxCreditsAccount(TaxAmountData $taxLine): bool
    {
        return ! ($taxLine->receivable || $taxLine->recoverable);
    }
}
