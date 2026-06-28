<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;

final class CurrencyRevaluationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $postings,
    ) {}

    /**
     * @param  list<array{
     *     exposure_key: string,
     *     carrying_amount: string,
     *     revalued_amount: string,
     *     description?: string|null,
     *     source_line_type?: string|null,
     *     source_line_id?: int|null
     * }>  $exposures
     */
    public function revalue(
        int $tenantId,
        ?int $organizationUnitId,
        string $exposureType,
        int $sourceId,
        string $postingDate,
        string $postingProfileCode,
        array $exposures,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?int $postedBy = null,
        string $gainProfileKey = 'unrealized_gain',
        string $lossProfileKey = 'unrealized_loss',
    ): PostingResultData {
        if ($exposures === []) {
            throw new InvalidArgumentException('Currency revaluation requires at least one exposure.');
        }

        $lines = [];
        foreach ($exposures as $index => $exposure) {
            $carryingAmount = $this->math->normalize((string) $exposure['carrying_amount']);
            $revaluedAmount = $this->math->normalize((string) $exposure['revalued_amount']);
            $difference = $this->math->sub($revaluedAmount, $carryingAmount);
            if ($this->math->isZero($difference)) {
                continue;
            }

            $amount = str_starts_with($difference, '-') ? substr($difference, 1) : $difference;
            $description = $exposure['description'] ?? 'Currency revaluation '.($index + 1);
            $exposureKey = trim((string) $exposure['exposure_key']);
            if ($exposureKey === '') {
                throw new InvalidArgumentException('Currency revaluation exposure profile key is required.');
            }

            if ($this->math->compare($difference, '0.000000') > 0) {
                $lines[] = new PostingLine(
                    accountCode: null,
                    accountName: 'Revalued exposure',
                    debit: $amount,
                    description: $description,
                    profileKey: $exposureKey,
                    sourceLineType: $exposure['source_line_type'] ?? $exposureType,
                    sourceLineId: $exposure['source_line_id'] ?? null,
                );
                $lines[] = new PostingLine(
                    accountCode: null,
                    accountName: 'Unrealized gain',
                    credit: $amount,
                    description: $description,
                    profileKey: $gainProfileKey,
                    sourceLineType: $exposure['source_line_type'] ?? $exposureType,
                    sourceLineId: $exposure['source_line_id'] ?? null,
                );

                continue;
            }

            $lines[] = new PostingLine(
                accountCode: null,
                accountName: 'Unrealized loss',
                debit: $amount,
                description: $description,
                profileKey: $lossProfileKey,
                sourceLineType: $exposure['source_line_type'] ?? $exposureType,
                sourceLineId: $exposure['source_line_id'] ?? null,
            );
            $lines[] = new PostingLine(
                accountCode: null,
                accountName: 'Revalued exposure',
                credit: $amount,
                description: $description,
                profileKey: $exposureKey,
                sourceLineType: $exposure['source_line_type'] ?? $exposureType,
                sourceLineId: $exposure['source_line_id'] ?? null,
            );
        }

        if ($lines === []) {
            throw new InvalidArgumentException('Currency revaluation has no non-zero differences to post.');
        }

        return $this->postings->post(new PostingContext(
            source: new PostingSourceData(
                sourceType: 'currency_revaluation_'.$exposureType,
                sourceId: $sourceId,
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
                sourceModule: 'finance',
                sourceNumber: 'REVAL-'.$sourceId,
                sourceDate: $postingDate,
            ),
            postingDate: $postingDate,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            lines: $lines,
            description: 'Currency revaluation: '.str_replace('_', ' ', $exposureType),
            postingProfileCode: $postingProfileCode,
        ), $postedBy);
    }
}
