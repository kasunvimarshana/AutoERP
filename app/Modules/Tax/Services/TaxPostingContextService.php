<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Models\FinanceAccount;
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
        string $counterpartyAccountCode,
        string $counterpartyAccountName,
        ?string $description = null,
        ?string $postingProfileCode = null,
    ): TaxPostingContext {
        if ($source->tenantId === null) {
            throw new InvalidArgumentException('Tax posting source tenant is required.');
        }

        $financeLines = [];
        foreach ($taxLines as $taxLine) {
            if (! $taxLine instanceof TaxAmountData || $this->math->isZero($taxLine->taxAmount)) {
                continue;
            }

            $account = $this->resolveAccount(
                (int) $source->tenantId,
                $source->organizationUnitId,
                $taxLine,
            );
            $lineDescription = $taxLine->taxCode.' '.$taxLine->taxName;
            $counterparty = new PostingLine(
                accountCode: $counterpartyAccountCode,
                accountName: $counterpartyAccountName,
                debit: $this->taxCreditsAccount($taxLine) ? $taxLine->taxAmount : '0.000000',
                credit: $this->taxCreditsAccount($taxLine) ? '0.000000' : $taxLine->taxAmount,
                description: $lineDescription.' counterparty',
                sourceLineType: 'tax',
                sourceLineId: $taxLine->taxId,
            );
            $taxPostingLine = new PostingLine(
                accountCode: (string) $account->code,
                accountName: (string) $account->name,
                debit: $this->taxCreditsAccount($taxLine) ? '0.000000' : $taxLine->taxAmount,
                credit: $this->taxCreditsAccount($taxLine) ? $taxLine->taxAmount : '0.000000',
                description: $lineDescription,
                profileKey: null,
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

    private function resolveAccount(int $tenantId, ?int $organizationUnitId, TaxAmountData $taxLine): FinanceAccount
    {
        $direction = $this->directionFor($taxLine);
        $query = TaxPostingProfile::query()
            ->with('account')
            ->where('tenant_id', $tenantId)
            ->where('tax_id', $taxLine->taxId)
            ->where('direction', $direction)
            ->where('active', true);
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $profile = $query->first();
        $account = $profile?->account;
        if (! $profile instanceof TaxPostingProfile || ! $account instanceof FinanceAccount) {
            throw new InvalidArgumentException("Tax account mapping is missing for tax [{$taxLine->taxCode}] direction [{$direction}].");
        }
        if (! (bool) $account->is_active || ! (bool) $account->is_posting_account) {
            throw new InvalidArgumentException("Tax account mapping for [{$taxLine->taxCode}] is inactive or not postable.");
        }
        if ((int) $account->tenant_id !== $tenantId || $account->organization_unit_id !== $organizationUnitId) {
            throw new InvalidArgumentException('Tax account mapping belongs to a different scope.');
        }

        return $account;
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
