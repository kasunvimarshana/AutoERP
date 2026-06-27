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

final class TaxPostingContextService
{
    private const PROFILE_KEY_TAX_RECEIVABLE = 'tax_receivable';
    private const PROFILE_KEY_TAX_PAYABLE = 'tax_payable';
    private const PROFILE_KEY_WITHHOLDING_RECEIVABLE = 'withholding_receivable';
    private const PROFILE_KEY_WITHHOLDING_PAYABLE = 'withholding_payable';

    private const COUNTERPARTY_RECEIVABLE = 'receivable';
    private const COUNTERPARTY_PAYABLE = 'payable';

    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  list<TaxAmountData>  $taxLines
     */
    public function build(
        PostingSourceData $source,
        string $postingDate,
        array $taxLines,
        string $counterpartyProfileKey,
        string $postingProfileCode,
        ?string $description = null,
    ): TaxPostingContext {
        if ($source->tenantId === null) {
            throw new InvalidArgumentException('Tax posting source tenant is required.');
        }
        if (! in_array($counterpartyProfileKey, [self::COUNTERPARTY_RECEIVABLE, self::COUNTERPARTY_PAYABLE], true)) {
            throw new InvalidArgumentException('Tax posting counterparty must be receivable or payable.');
        }
        if (trim($postingProfileCode) === '') {
            throw new InvalidArgumentException('Tax posting profile code is required.');
        }

        $financeLines = [];
        foreach ($taxLines as $taxLine) {
            if (! $taxLine instanceof TaxAmountData || $this->math->isZero($taxLine->taxAmount)) {
                continue;
            }

            $taxProfileKey = $this->taxProfileKey($taxLine, $counterpartyProfileKey);
            $taxLineCredits = $this->taxLineCredits($taxLine, $counterpartyProfileKey);
            $lineDescription = $taxLine->taxCode.' '.$taxLine->taxName;

            $financeLines[] = new PostingLine(
                profileKey: $counterpartyProfileKey,
                debit: $taxLineCredits ? $taxLine->taxAmount : '0.000000',
                credit: $taxLineCredits ? '0.000000' : $taxLine->taxAmount,
                description: $lineDescription.' counterparty',
                sourceLineType: 'tax',
                sourceLineId: $taxLine->taxId,
            );
            $financeLines[] = new PostingLine(
                profileKey: $taxProfileKey,
                debit: $taxLineCredits ? '0.000000' : $taxLine->taxAmount,
                credit: $taxLineCredits ? $taxLine->taxAmount : '0.000000',
                description: $lineDescription,
                sourceLineType: 'tax',
                sourceLineId: $taxLine->taxId,
                contextType: 'tax',
                contextId: $taxLine->taxId,
            );
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

    private function taxProfileKey(TaxAmountData $taxLine, string $counterpartyProfileKey): string
    {
        if ($taxLine->isWithholding) {
            return $counterpartyProfileKey === self::COUNTERPARTY_RECEIVABLE
                ? self::PROFILE_KEY_WITHHOLDING_RECEIVABLE
                : self::PROFILE_KEY_WITHHOLDING_PAYABLE;
        }
        if ($taxLine->receivable || $taxLine->recoverable) {
            return self::PROFILE_KEY_TAX_RECEIVABLE;
        }
        if ($taxLine->payable) {
            return self::PROFILE_KEY_TAX_PAYABLE;
        }

        throw new InvalidArgumentException("Tax direction is not defined for tax [{$taxLine->taxCode}].");
    }

    private function taxLineCredits(TaxAmountData $taxLine, string $counterpartyProfileKey): bool
    {
        if ($taxLine->isWithholding) {
            return $counterpartyProfileKey === self::COUNTERPARTY_PAYABLE;
        }

        return ! ($taxLine->receivable || $taxLine->recoverable);
    }
}
