<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class FinancePostingLine extends PostingLine
{
    /**
     * @param  array<string, string|null>  $dimensions
     */
    public function __construct(
        ?string $accountCode = null,
        ?string $accountName = null,
        string $debit = '0.000000',
        string $credit = '0.000000',
        ?string $description = null,
        ?string $profileKey = null,
        ?string $dimensionCode = null,
        ?string $sourceLineType = null,
        ?int $sourceLineId = null,
        array $dimensions = [],
    ) {
        parent::__construct(
            accountName: $accountName,
            lineName: null,
            debit: $debit,
            credit: $credit,
            description: $description,
            profileKey: $profileKey,
            dimensionCode: $dimensionCode,
            sourceLineType: $sourceLineType,
            sourceLineId: $sourceLineId,
            dimensions: $dimensions,
        );
    }
}
